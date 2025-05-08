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

namespace Pratech\Warehouse\Model\ResourceModel\WarehouseInventory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'inventory_id';

    /**
     * Join product data to collection
     *
     * @return $this
     */
//    public function joinProductData(): static
//    {
//        $this->getSelect()->joinLeft(
//            ['product' => $this->getTable('catalog_product_entity')],
//            'main_table.sku = product.sku',
//            [
//                'product_id' => 'entity_id'
//            ]
//        );
//
//        return $this;
//    }

    /**
     * Add filter by active warehouse
     *
     * @param bool $status
     * @return $this
     */
//    public function addActiveWarehouseFilter(bool $status = true): static
//    {
//        $this->addWarehouseData();
//        $this->addFieldToFilter('warehouse.is_active', (int)$status);
//        return $this;
//    }

    /**
     * Add warehouse data to collection
     *
     * @return $this
     */
//    public function addWarehouseData(): static
//    {
//        $this->getSelect()->joinLeft(
//            ['warehouse' => $this->getTable('pratech_warehouse')],
//            'main_table.warehouse_code = warehouse.warehouse_code',
//            [
//                'warehouse_name' => 'name',
//                'warehouse_code',
//                'warehouse_pincode' => 'pincode',
//                'warehouse_is_active' => 'is_active'
//            ]
//        );
//
//        return $this;
//    }

    /**
     * Add filter by warehouse
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
//    public function addFieldToFilter($field, $condition = null): static
//    {
//        if ($field === 'warehouse_name') {
//            $field = 'warehouse.name';
//        } elseif ($field === 'warehouse_code') {
//            $field = 'warehouse.warehouse_code';
//        }
//
//        return parent::addFieldToFilter($field, $condition);
//    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            \Pratech\Warehouse\Model\WarehouseInventory::class,
            WarehouseInventory::class
        );
    }
}
