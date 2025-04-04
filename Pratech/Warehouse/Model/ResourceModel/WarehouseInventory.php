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

namespace Pratech\Warehouse\Model\ResourceModel;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WarehouseInventory extends AbstractDb
{
    /**
     * Update multiple warehouse inventories
     *
     * @param array $inventoryData Format: ['W03' => ['SKU1' => 10, 'SKU2' => 20], 'W04' => ['SKU1' => 15]]
     * @return array Format: ['success' => true/false, 'affected_rows' => X, 'errors' => []]
     */
    public function bulkUpdateInventory(array $inventoryData): array
    {
        $result = [
            'success' => true,
            'affected_rows' => 0,
            'errors' => []
        ];

        $connection = $this->getConnection();
        try {
            $connection->beginTransaction();

            foreach ($inventoryData as $warehouseCode => $skuQuantities) {
                try {
                    $affectedRows = $this->updateInventoryByWarehouseCode($warehouseCode, $skuQuantities);
                    $result['affected_rows'] += $affectedRows;
                } catch (LocalizedException $e) {
                    $result['errors'][] = [
                        'warehouse_code' => $warehouseCode,
                        'message' => $e->getMessage()
                    ];
                }
            }

            if (empty($result['errors'])) {
                $connection->commit();
            } else {
                $connection->rollBack();
                $result['success'] = false;
                $result['affected_rows'] = 0;
            }
        } catch (Exception $e) {
            $connection->rollBack();
            $result['success'] = false;
            $result['errors'][] = [
                'message' => $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Update or insert warehouse inventory
     *
     * @param string $warehouseCode
     * @param array $skuQuantities Format: ['SKU1' => 10, 'SKU2' => 20]
     * @return int Number of affected rows
     * @throws LocalizedException
     */
    public function updateInventoryByWarehouseCode(string $warehouseCode, array $skuQuantities): int
    {
        if (empty($skuQuantities)) {
            return 0;
        }

        try {
            $inventoryData = [];
            foreach ($skuQuantities as $sku => $quantity) {
                $inventoryData[] = [
                    'warehouse_code' => $warehouseCode,
                    'sku' => $sku,
                    'quantity' => $quantity
                ];
            }

            return $this->getConnection()->insertOnDuplicate(
                $this->getMainTable(),
                $inventoryData,
                ['quantity']
            );
        } catch (Exception $e) {
            throw new LocalizedException(__('Failed to update inventory: %1', $e->getMessage()));
        }
    }

    /**
     * Process individual inventory item
     *
     * @param array $warehousesInventory
     * @return array
     * @throws LocalizedException
     */
    public function processInventoryItem(array $warehousesInventory): array
    {
        $data = [];
        try {
            $tableName = $this->getMainTable();

            foreach ($warehousesInventory as $warehouseInventory) {
                $data[] = [
                    'sku' => $warehouseInventory['sku'],
                    'warehouse_code' => $warehouseInventory['warehouse_code'],
                    'quantity' => $warehouseInventory['total_qty'],
                ];
            }

            if (!empty($data)) {
                $this->getConnection()->insertOnDuplicate(
                    $tableName,
                    $data,
                    ['quantity']
                );
            }
            return $data;

        } catch (Exception $e) {
            throw new LocalizedException(__(
                'Error processing inventory item: %1, Data: %2',
                $e->getMessage(),
                $data
            ));
        }
    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_warehouse_inventory', 'inventory_id');
    }
}
