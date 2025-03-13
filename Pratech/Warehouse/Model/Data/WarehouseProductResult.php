<?php
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

class WarehouseProductResult extends AbstractExtensibleObject implements WarehouseProductResultInterface
{
    /**
     * @inheritDoc
     */
    public function getWarehouseCode()
    {
        return $this->_get('warehouse_code');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseCode(string $warehouseCode)
    {
        return $this->setData('warehouse_code', $warehouseCode);
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseName()
    {
        return $this->_get('warehouse_name');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseName(string $warehouseName)
    {
        return $this->setData('warehouse_name', $warehouseName);
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->_get('items') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items)
    {
        return $this->setData('items', $items);
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount()
    {
        return (int)$this->_get('total_count');
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount(int $totalCount)
    {
        return $this->setData('total_count', $totalCount);
    }

    /**
     * @inheritDoc
     */
    public function getAvailableFilters()
    {
        return $this->_get('available_filters') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setAvailableFilters(array $filters)
    {
        return $this->setData('available_filters', $filters);
    }
}
