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

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Logger\InventorySyncLogger;
use Pratech\Warehouse\Helper\Vinculum;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory;

class UpdateWarehouseStock
{
    /**
     * @param Vinculum $vinculumHelper
     * @param ResourceConnection $resourceConnection
     * @param WarehouseInventory $warehouseInventoryResource
     * @param InventorySyncLogger $inventorySyncLogger
     */
    public function __construct(
        private Vinculum            $vinculumHelper,
        private ResourceConnection  $resourceConnection,
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

    /**
     * Get enabled product SKUs using direct SQL
     *
     * @return array
     */
    public function getEnabledSkus(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $statusTable = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $select = $connection->select()
            ->from(['p' => $productTable], ['sku'])
            ->join(
                ['status' => $statusTable],
                'p.entity_id = status.entity_id AND status.attribute_id = (SELECT attribute_id FROM ' .
                $this->resourceConnection->getTableName('eav_attribute') .
                ' WHERE attribute_code = "status" AND entity_type_id = 4)',
                []
            )
            ->where('status.value = ?', Status::STATUS_ENABLED);

        return $connection->fetchCol($select);
    }
}
