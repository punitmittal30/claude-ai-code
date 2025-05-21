<?php

namespace Pratech\Warehouse\Observer;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Zend_Db_Expr;

class ReduceWarehouseInventoryObserver implements ObserverInterface
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
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return;
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('pratech_warehouse_inventory');

        // Begin transaction
        $connection->beginTransaction();
        $locksAcquired = [];

        try {
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductType() == 'configurable' || $item->getHasChildren()) {
                    continue;
                }

                if ($item->getProductType() == 'bundle') {
                    continue;
                }

                $sku = $item->getSku();
                $qty = $item->getQtyOrdered();
                $warehouseCode = $item->getWarehouseCode();

                // Skip if no warehouse code is assigned
                if (!$warehouseCode || $warehouseCode == 'NA' || $warehouseCode == 'DRS') {
                    continue;
                }

                // Create a unique lock name for this SKU and warehouse combination
                $lockName = "inventory_update_{$sku}_{$warehouseCode}";

                // Try to acquire a lock with timeout (5 seconds)
                if ($this->lockManager->lock($lockName, 5)) {
                    // Add to our list of acquired locks
                    $locksAcquired[] = $lockName;

                    // Check if the warehouse/SKU combination exists before updating
                    $select = $connection->select()
                        ->from($tableName)
                        ->where('sku = ?', $sku)
                        ->where('warehouse_code = ?', $warehouseCode);

                    $inventoryRecord = $connection->fetchRow($select);

                    if (!$inventoryRecord) {
                        continue;
                    }

                    // Check if there's enough inventory to deduct
                    $currentQty = (float)$inventoryRecord['quantity'];
                    if ($currentQty < $qty) {
                        // Would result in negative inventory, skip and log
                        $this->inventorySyncLogger->warning(
                            'Cannot reduce inventory below zero, skipping reduction',
                            [
                                'sku' => $sku,
                                'warehouse_code' => $warehouseCode,
                                'current_qty' => $currentQty,
                                'requested_reduction' => $qty,
                                'order_id' => $order->getId(),
                                'item_id' => $item->getItemId()
                            ]
                        );
                        continue;
                    }

                    // Update inventory with lock in place
                    $result = $connection->update(
                        $tableName,
                        ['quantity' => new Zend_Db_Expr('quantity - ' . $qty)],
                        [
                            'sku = ?' => $sku,
                            'warehouse_code = ?' => $warehouseCode,
                            // Add additional condition to prevent negative inventory
                            'quantity >= ?' => $qty
                        ]
                    );

                    if (!$result) {
                        $this->inventorySyncLogger->warning(
                            'Inventory update failed - possibly insufficient stock',
                            [
                                'sku' => $sku,
                                'warehouse_code' => $warehouseCode,
                                'qty' => $qty,
                                'order_id' => $order->getId(),
                                'item_id' => $item->getItemId()
                            ]
                        );
                    }

                } else {
                    throw new Exception(
                        "Could not acquire lock for SKU {$sku} in warehouse {$warehouseCode} after 5 seconds."
                    );
                }
            }
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->inventorySyncLogger->error('Error reducing warehouse inventory: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id' => $order->getId()
            ]);
        } finally {
            foreach ($locksAcquired as $lockName) {
                $this->lockManager->unlock($lockName);
            }
        }
    }
}
