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

namespace Pratech\Warehouse\Plugin;

use Magento\Framework\Model\AbstractModel;
use Pratech\Warehouse\Model\Cache\DarkStoreCache;
use Pratech\Warehouse\Model\ResourceModel\Warehouse as WarehouseResource;

/**
 * Plugin to clear dark store cache when warehouse data changes
 */
class WarehouseResourcePlugin
{
    /**
     * @param DarkStoreCache $darkStoreCache
     */
    public function __construct(
        private DarkStoreCache $darkStoreCache
    ) {
    }

    /**
     * After save handler
     *
     * @param WarehouseResource $subject
     * @param WarehouseResource $result
     * @param AbstractModel $warehouse
     * @return WarehouseResource
     */
    public function afterSave(
        WarehouseResource $subject,
        WarehouseResource $result,
        AbstractModel     $warehouse
    ): WarehouseResource {
        if ($warehouse->getIsDarkStore() || $warehouse->getOrigData('is_dark_store')) {
            $this->darkStoreCache->clearDarkStoreCache();
        }

        return $result;
    }

    /**
     * After delete handler
     *
     * @param WarehouseResource $subject
     * @param WarehouseResource $result
     * @param AbstractModel $warehouse
     * @return WarehouseResource
     */
    public function afterDelete(
        WarehouseResource $subject,
        WarehouseResource $result,
        AbstractModel     $warehouse
    ): WarehouseResource {
        if ($warehouse->getIsDarkStore()) {
            $this->darkStoreCache->clearDarkStoreCache();
        }

        return $result;
    }
}
