<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Pratech\Warehouse\Api\Data\FilterResultInterface;

/**
 * Filter result implementation
 */
class FilterResult extends AbstractExtensibleObject implements FilterResultInterface
{
    /**
     * @inheritDoc
     */
    public function getWarehouseCode(): ?string
    {
        return $this->_get('warehouse_code');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseCode(string $warehouseCode): self
    {
        return $this->setData('warehouse_code', $warehouseCode);
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseName(): ?string
    {
        return $this->_get('warehouse_name');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseName(string $warehouseName): self
    {
        return $this->setData('warehouse_name', $warehouseName);
    }

    /**
     * @inheritDoc
     */
    public function getPriceRanges(): array
    {
        return $this->_get('price_ranges') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setPriceRanges(array $priceRanges): self
    {
        return $this->setData('price_ranges', $priceRanges);
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): array
    {
        return $this->_get('categories') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setCategories(array $categories): self
    {
        return $this->setData('categories', $categories);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->_get('attributes') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(array $attributes): self
    {
        return $this->setData('attributes', $attributes);
    }
}
