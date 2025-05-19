<?php

namespace Pratech\Warehouse\Service;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Lock\LockManagerInterface;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Zend_Db_Expr;

class WarehouseInventoryService
{
    /**
     * @param ResourceConnection $resource
     * @param InventorySyncLogger $inventorySyncLogger
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        private ResourceConnection   $resource,
        private InventorySyncLogger  $inventorySyncLogger,
        private LockManagerInterface $lockManager
    ) {
    }

    /**
     * Update inventory quantity
     *
     * @param string $sku
     * @param string $warehouseCode
     * @param float $qtyChange Positive for increase, negative for decrease
     * @param string $reference Reference info (order ID, etc.)
     * @param string $type Type of inventory change (order, cancel, etc.)
     * @return bool Success flag
     */
    public function updateInventory(
        string $sku,
        string $warehouseCode,
        float $qtyChange,
        string $reference,
        string $type
    ): bool {
        // Skip if no warehouse code or using special codes like NA or DRS
        if (!$warehouseCode || $warehouseCode == 'NA' || $warehouseCode == 'DRS') {
            return false;
        }

        $tableName = $this->resource->getTableName('pratech_warehouse_inventory');
        $connection = $this->resource->getConnection();
        $lockName = "inventory_update_{$sku}_{$warehouseCode}";

        // Try to acquire a lock
        if ($this->lockManager->lock($lockName, 5)) {
            try {
                $connection->beginTransaction();

                // Check if the record exists
                $select = $connection->select()
                    ->from($tableName)
                    ->where('sku = ?', $sku)
                    ->where('warehouse_code = ?', $warehouseCode);

                $inventoryRecord = $connection->fetchRow($select);

                if (!$inventoryRecord) {
                    // Record doesn't exist
                    if ($qtyChange > 0) {
                        // If adding inventory, create a new record
                        $connection->insert(
                            $tableName,
                            [
                                'sku' => $sku,
                                'warehouse_code' => $warehouseCode,
                                'quantity' => $qtyChange
                            ]
                        );
                        $success = true;
                    } else {
                        // Don't create negative inventory
                        $success = false;
                    }
                } else {
                    // Record exists, check if update would result in negative inventory
                    $currentQty = (float)$inventoryRecord['quantity'];
                    $newQty = $currentQty + $qtyChange;

                    if ($newQty < 0 && $qtyChange < 0) {
                        // Would result in negative inventory
                        $this->inventorySyncLogger->warning(
                            'Cannot reduce inventory below zero',
                            [
                                'sku' => $sku,
                                'warehouse_code' => $warehouseCode,
                                'current_qty' => $currentQty,
                                'requested_change' => $qtyChange,
                                'reference' => $reference,
                                'type' => $type
                            ]
                        );
                        $success = false;
                    } else {
                        // Safe to update
                        $result = $connection->update(
                            $tableName,
                            ['quantity' => new Zend_Db_Expr('quantity + ' . $qtyChange)],
                            [
                                'sku = ?' => $sku,
                                'warehouse_code = ?' => $warehouseCode,
                                // For safety, ensure we don't go negative when reducing
                                $qtyChange < 0 ? 'quantity >= ?' : '1=1' => abs($qtyChange)
                            ]
                        );

                        $success = (bool)$result;
                    }
                }

                $connection->commit();
            } catch (Exception $e) {
                $connection->rollBack();
                $this->inventorySyncLogger->error('Error updating inventory: ' . $e->getMessage(), [
                    'exception' => $e,
                    'sku' => $sku,
                    'warehouse_code' => $warehouseCode,
                    'change' => $qtyChange,
                    'reference' => $reference,
                    'type' => $type
                ]);
                $success = false;
            } finally {
                $this->lockManager->unlock($lockName);
            }
        } else {
            $this->inventorySyncLogger->warning(
                'Could not acquire lock for inventory update',
                [
                    'sku' => $sku,
                    'warehouse_code' => $warehouseCode,
                    'reference' => $reference,
                    'type' => $type
                ]
            );
            $success = false;
        }

        return $success;
    }
}
