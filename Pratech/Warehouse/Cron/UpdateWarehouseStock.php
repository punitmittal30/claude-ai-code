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

namespace Pratech\Warehouse\Cron;

use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Helper\Vinculum;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory;

class UpdateWarehouseStock
{
    /**
     * @param Vinculum $vinculumHelper
     * @param WarehouseInventory $warehouseInventoryResource
     * @param InventorySyncLogger $inventorySyncLogger
     */
    public function __construct(
        private Vinculum            $vinculumHelper,
        private WarehouseInventory  $warehouseInventoryResource,
        private InventorySyncLogger $inventorySyncLogger
    ) {
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            if ($this->vinculumHelper->isVinculumSyncEnabled()) {
                $warehousesInventoryData = $this->vinculumHelper->getAllWarehousesInventory();
                $result = $this->warehouseInventoryResource->bulkUpdateInventory($warehousesInventoryData);
            }
        } catch (LocalizedException $exception) {
            $this->inventorySyncLogger->error($exception->getMessage());
        }
    }
}
