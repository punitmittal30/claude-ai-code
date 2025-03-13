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
use Pratech\Warehouse\Api\WarehouseProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * GraphQL resolver for warehouse products
 */
class WarehouseProducts implements ResolverInterface
{
    /**
     * @var WarehouseProductRepositoryInterface
     */
    private $warehouseProductRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WarehouseProductRepositoryInterface $warehouseProductRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        WarehouseProductRepositoryInterface $warehouseProductRepository,
        LoggerInterface $logger
    ) {
        $this->warehouseProductRepository = $warehouseProductRepository;
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
        $pageSize = $args['pageSize'] ?? 20;
        $currentPage = $args['currentPage'] ?? 1;

        // Extract sort information
        $sortField = null;
        $sortDirection = 'ASC';

        if (isset($args['sort'])) {
            $sortData = current($args['sort']);
            if (is_array($sortData)) {
                $sortField = key($args['sort']);
                $sortDirection = $sortData;
            }
        }

        // Extract filter information
        $filters = null;
        if (isset($args['filter']) && !empty($args['filter'])) {
            $filters = $this->processFilters($args['filter']);
        }

        try {
            $result = $this->warehouseProductRepository->getProductsByWarehouse(
                $warehouseCode,
                (int)$pageSize,
                (int)$currentPage,
                $sortField,
                $sortDirection,
                $filters
            );

            // Format the response
            return [
                'items' => $result->getItems(),
                'total_count' => $result->getTotalCount(),
                'page_info' => [
                    'page_size' => $pageSize,
                    'current_page' => $currentPage,
                    'total_pages' => ceil($result->getTotalCount() / $pageSize)
                ],
                'warehouse_code' => $result->getWarehouseCode(),
                'warehouse_name' => $result->getWarehouseName()
            ];
        } catch (LocalizedException $e) {
            $this->logger->error('GraphQL warehouse products error: ' . $e->getMessage());
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in warehouse products resolver: ' . $e->getMessage());
            throw new GraphQlInputException(__('An error occurred while retrieving products'));
        }
    }

    /**
     * Process GraphQL filter input into format usable by repository
     *
     * @param array $filterInput
     * @return array
     */
    private function processFilters(array $filterInput): array
    {
        $processedFilters = [];

        foreach ($filterInput as $field => $conditions) {
            if (empty($conditions)) {
                continue;
            }

            foreach ($conditions as $conditionType => $value) {
                switch ($conditionType) {
                    case 'eq':
                        $processedFilters[$field] = ['value' => $value, 'condition_type' => 'eq'];
                        break;
                    case 'in':
                        $processedFilters[$field] = ['value' => $value, 'condition_type' => 'in'];
                        break;
                    case 'match':
                        $processedFilters[$field] = ['value' => '%' . $value . '%', 'condition_type' => 'like'];
                        break;
                    case 'from':
                    case 'to':
                        if (!isset($processedFilters[$field])) {
                            $processedFilters[$field] = ['value' => [], 'condition_type' => 'range'];
                        }
                        if ($conditionType === 'from') {
                            $processedFilters[$field]['value'][0] = $value;
                        } else {
                            $processedFilters[$field]['value'][1] = $value;
                        }
                        break;
                }
            }
        }

        return $processedFilters;
    }
}
