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

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Helper\FilterHelper;
use Psr\Log\LoggerInterface;

/**
 * GraphQL resolver for available warehouse product filters
 */
class AvailableFilters implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var WarehouseRepositoryInterface
     */
    private $warehouseRepository;

    /**
     * @var FilterHelper
     */
    private $filterHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param FilterHelper $filterHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        WarehouseRepositoryInterface $warehouseRepository,
        FilterHelper $filterHelper,
        LoggerInterface $logger
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->warehouseRepository = $warehouseRepository;
        $this->filterHelper = $filterHelper;
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
        if (!isset($args['warehouseCode']) || empty($args['warehouseCode'])) {
            throw new GraphQlInputException(__('Warehouse code is required'));
        }

        $warehouseCode = $args['warehouseCode'];
        $categoryId = $args['categoryId'] ?? null;

        try {
            // Get warehouse info
            $warehouse = $this->getWarehouseByCode($warehouseCode);

            // Create product collection with warehouse inventory filter
            $collection = $this->createProductCollection($warehouseCode, $categoryId);

            // Get available filters
            $filters = $this->filterHelper->getAvailableFilters($collection, $categoryId);

            // Format response
            return [
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $warehouse->getName(),
                'price_ranges' => $filters['price'] ?? [],
                'categories' => $filters['category'] ?? [],
                'attributes' => array_values($filters['attributes'] ?? [])
            ];
        } catch (LocalizedException $e) {
            $this->logger->error('GraphQL warehouse filters error: ' . $e->getMessage());
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in warehouse filters resolver: ' . $e->getMessage());
            throw new GraphQlInputException(__('An error occurred while retrieving filters'));
        }
    }

    /**
     * Get warehouse by code
     *
     * @param string $warehouseCode
     * @return \Pratech\Warehouse\Api\Data\WarehouseInterface
     * @throws LocalizedException
     */
    private function getWarehouseByCode(string $warehouseCode)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('warehouse_code', $warehouseCode)
            ->create();

        $result = $this->warehouseRepository->getList($searchCriteria);

        if ($result->getTotalCount() === 0) {
            throw new LocalizedException(__('Warehouse with code "%1" does not exist.', $warehouseCode));
        }

        $items = $result->getItems();
        return reset($items);
    }

    /**
     * Create product collection filtered by warehouse
     *
     * @param string $warehouseCode
     * @param int|null $categoryId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function createProductCollection(string $warehouseCode, ?int $categoryId = null)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        // Join with warehouse inventory
        $collection->getSelect()->join(
            ['inventory' => $collection->getTable('pratech_warehouse_inventory')],
            'e.sku = inventory.sku',
            ['warehouse_quantity' => 'inventory.quantity']
        )->where('inventory.warehouse_code = ?', $warehouseCode)
            ->where('inventory.quantity > 0');

        // Filter by category if provided
        if ($categoryId) {
            $collection->addCategoriesFilter(['eq' => $categoryId]);
        }

        return $collection;
    }
}
