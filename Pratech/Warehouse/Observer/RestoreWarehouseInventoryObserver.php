<?php

namespace Pratech\Warehouse\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Pratech\Warehouse\Service\WarehouseInventoryService;

class RestoreWarehouseInventoryObserver implements ObserverInterface
{
    /**
     * @param WarehouseInventoryService $warehouseInventoryService
     * @param InventorySyncLogger $inventorySyncLogger
     */
    public function __construct(
        private WarehouseInventoryService $warehouseInventoryService,
        private InventorySyncLogger $inventorySyncLogger
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

        try {
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductType() == 'configurable' || $item->getHasChildren()) {
                    continue;
                }

                if ($item->getProductType() == 'bundle') {
                    continue;
                }

                $sku = $item->getSku();
                // Calculate the quantity that should be returned to stock
                $qtyToRestore = $item->getQtyOrdered() - $item->getQtyShipped();
                $warehouseCode = $item->getWarehouseCode();

                // Skip if no warehouse code or using special codes like NA or DRS
                if (!$warehouseCode || $warehouseCode == 'NA' || $warehouseCode == 'DRS') {
                    continue;
                }

                // Only process if there's actually quantity to restore
                if ($qtyToRestore <= 0) {
                    continue;
                }

                // Use the service to update inventory (positive qty for addition)
                $result = $this->warehouseInventoryService->updateInventory(
                    $sku,
                    $warehouseCode,
                    $qtyToRestore,
                    'cancel_' . $order->getIncrementId(),
                    'order_cancellation'
                );

                if (!$result) {
                    $this->inventorySyncLogger->warning(
                        'Failed to restore inventory for canceled item',
                        [
                            'sku' => $sku,
                            'warehouse_code' => $warehouseCode,
                            'qty' => $qtyToRestore,
                            'order_id' => $order->getId(),
                            'item_id' => $item->getItemId()
                        ]
                    );
                }
            }
        } catch (Exception $e) {
            $this->inventorySyncLogger->error('Error restoring warehouse inventory: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id' => $order->getId()
            ]);
        }
    }
}
