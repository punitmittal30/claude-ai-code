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

namespace Pratech\Warehouse\Model\ResourceModel\WarehouseSla;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'sla_id';

    /**
     * Add filter by warehouse status
     *
     * @param bool $status
     * @return $this
     */
    public function addActiveWarehouseFilter(bool $status = true): static
    {
        $this->addWarehouseData();
        $this->addFieldToFilter('warehouse.is_active', (int)$status);
        return $this;
    }

    /**
     * Add warehouse data to collection
     *
     * @return $this
     */
    public function addWarehouseData(): static
    {
        $this->getSelect()->joinLeft(
            ['warehouse' => $this->getTable('pratech_warehouse')],
            'main_table.warehouse_pincode = warehouse.pincode',
            [
                'warehouse_name' => 'name',
                'warehouse_pincode' => 'warehouse_pincode',
                'warehouse_is_active' => 'is_active'
            ]
        );

        return $this;
    }

    /**
     * Add filter by warehouse
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null): static
    {
        if ($field === 'warehouse_name') {
            $field = 'warehouse.name';
        } elseif ($field === 'warehouse_pincode') {
            $field = 'warehouse.warehouse_pincode';
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add filter by customer pincode
     *
     * @param int|array $pincode
     * @return $this
     */
    public function addPincodeFilter(int|array $pincode): static
    {
        if (is_array($pincode)) {
            $this->addFieldToFilter('main_table.customer_pincode', ['in' => $pincode]);
        } else {
            $this->addFieldToFilter('main_table.customer_pincode', $pincode);
        }
        return $this;
    }

    /**
     * Add filter by delivery time
     *
     * @param int $time
     * @param string $condition
     * @return $this
     */
    public function addDeliveryTimeFilter(int $time, string $condition = 'eq'): static
    {
        $this->addFieldToFilter('main_table.delivery_time', [$condition => $time]);
        return $this;
    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            \Pratech\Warehouse\Model\WarehouseSla::class,
            WarehouseSla::class
        );
    }
}
