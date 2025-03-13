<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\ResourceConnection;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * GraphQL resolver for dark store filters by pincode
 */
class DarkStoreFilters implements ResolverInterface
{
    /**
     * @var WarehouseRepositoryInterface
     */
    private $warehouseRepository;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarehouseRepositoryInterface $warehouseRepository,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->warehouseRepository = $warehouseRepository;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['pincode']) || empty($args['pincode'])) {
            throw new GraphQlInputException(__('Pincode is required'));
        }

        $pincode = (int)$args['pincode'];
        $categoryId = $args['categoryId'] ?? null;

        try {
            // Get nearest dark store for this pincode
            $darkStore = $this->findNearestDarkStore($pincode);

            if (!$darkStore) {
                throw new GraphQlNoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            }

            // Delegate to warehouse filters resolver
            $warehouseCode = $darkStore->getWarehouseCode();

            $filterArgs = [
                'warehouseCode' => $warehouseCode
            ];

            if ($categoryId) {
                $filterArgs['categoryId'] = $categoryId;
            }

            // Create the resolver for warehouse filters
            $warehouseFiltersResolver = \Magento\Framework\App\ObjectManager::getInstance()
                ->create(AvailableFilters::class);

            return $warehouseFiltersResolver->resolve($field, $context, $info, $value, $filterArgs);
        } catch (GraphQlNoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in dark store filters resolver: ' . $e->getMessage());
            throw new GraphQlInputException(__('An error occurred while retrieving filters'));
        }
    }

    /**
     * Find nearest dark store for a pincode
     *
     * @param int $pincode
     * @return \Pratech\Warehouse\Api\Data\WarehouseInterface|null
     */
    private function findNearestDarkStore(int $pincode)
    {
        try {
            // Get all dark stores
            $darkStores = $this->warehouseRepository->getDarkStores();

            if (empty($darkStores)) {
                return null;
            }

            // Find SLA data to determine nearest warehouse by delivery time
            $connection = $this->resource->getConnection();
            $slaTable = $this->resource->getTableName('pratech_warehouse_sla');

            $select = $connection->select()
                ->from($slaTable)
                ->where('customer_pincode = ?', $pincode)
                ->order('priority ASC')
                ->order('delivery_time ASC')
                ->limit(1);

            $slaData = $connection->fetchRow($select);

            if (!$slaData) {
                // No SLA data found, return first dark store
                return reset($darkStores);
            }

            // Get warehouse by pincode
            $warehousePincode = $slaData['warehouse_pincode'];

            foreach ($darkStores as $darkStore) {
                if ((int)$darkStore->getPincode() === (int)$warehousePincode) {
                    return $darkStore;
                }
            }

            // If no matching warehouse found, return first dark store
            return reset($darkStores);
        } catch (\Exception $e) {
            $this->logger->error('Error finding nearest dark store: ' . $e->getMessage());
            return null;
        }
    }
}
