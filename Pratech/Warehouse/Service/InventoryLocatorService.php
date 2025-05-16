<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Warehouse\Service;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Pratech\Warehouse\Api\PincodeRepositoryInterface;

class InventoryLocatorService
{
    /**
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param PincodeRepositoryInterface $pincodeRepository
     * @param DarkStoreLocatorService $darkStoreLocator
     */
    public function __construct(
        private LoggerInterface $logger,
        private ResourceConnection $resourceConnection,
        private PincodeRepositoryInterface $pincodeRepository,
        private DarkStoreLocatorService $darkStoreLocator
    ) {
    }

    /**
     * Get total available inventory quantity for a product at a specific pincode
     *
     * @param string $sku
     * @param int $pincode
     * @return int Total available quantity across all applicable warehouses
     */
    public function getInventoryQtyByPincode(string $sku, int $pincode): int
    {
        try {
            // Check if pincode is serviceable
            $pincodeData = $this->pincodeRepository->getByCode($pincode);
            if (!$pincodeData->getIsServiceable()) {
                return 0;
            }

            // Get warehouses and inventory in minimal queries
            $warehouseCodes = $this->getWarehouseCodesByPincode($pincode);
            if (empty($warehouseCodes)) {
                return 0;
            }

            return $this->getTotalInventoryForSku($sku, $warehouseCodes);
        } catch (Exception $e) {
            $this->logger->error('Error getting inventory quantity: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get warehouse codes (both regular and dark store) for a pincode
     *
     * @param int $pincode
     * @return array
     */
    private function getWarehouseCodesByPincode(int $pincode): array
    {
        $warehouseCodes = [];

        try {
            $connection = $this->resourceConnection->getConnection();

            // Get regular warehouses in a single query
            $select = $connection->select()
                ->from(
                    ['sla' => $this->resourceConnection->getTableName('pratech_warehouse_sla')],
                    []
                )
                ->join(
                    ['w' => $this->resourceConnection->getTableName('pratech_warehouse')],
                    'sla.warehouse_pincode = w.pincode',
                    ['warehouse_code']
                )
                ->where('sla.customer_pincode = ?', $pincode)
                ->where('w.is_active = ?', 1)
                ->where('w.is_dark_store = ?', 0);

            $regularWarehouses = $connection->fetchCol($select);
            $warehouseCodes = array_merge($warehouseCodes, $regularWarehouses);

            // Get nearest dark store
            try {
                $darkStore = $this->darkStoreLocator->findNearestDarkStore($pincode);
                if (!empty($darkStore) && isset($darkStore['warehouse_code'])) {
                    $warehouseCodes[] = $darkStore['warehouse_code'];
                }
            } catch (NoSuchEntityException $e) {
                // No dark store available, continue without it
            }

            return array_unique($warehouseCodes);
        } catch (Exception $e) {
            $this->logger->error('Error getting warehouse codes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total inventory quantity for a SKU across multiple warehouses
     *
     * @param string $sku
     * @param array $warehouseCodes
     * @return int
     */
    private function getTotalInventoryForSku(string $sku, array $warehouseCodes): int
    {
        if (empty($warehouseCodes)) {
            return 0;
        }

        try {
            $connection = $this->resourceConnection->getConnection();

            // Get total quantity in a single query
            $select = $connection->select()
                ->from(
                    ['i' => $this->resourceConnection->getTableName('pratech_warehouse_inventory')],
                    ['total_qty' => 'SUM(quantity)']
                )
                ->where('i.sku = ?', $sku)
                ->where('i.warehouse_code IN (?)', $warehouseCodes);

            return (int)$connection->fetchOne($select);
        } catch (Exception $e) {
            $this->logger->error('Error getting total inventory: ' . $e->getMessage());
            return 0;
        }
    }
}
