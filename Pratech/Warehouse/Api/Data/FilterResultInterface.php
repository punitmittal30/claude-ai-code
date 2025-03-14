<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Api\Data;

/**
 * Interface for filter results
 */
interface FilterResultInterface
{
    /**
     * Get warehouse code
     *
     * @return string|null
     */
    public function getWarehouseCode(): ?string;

    /**
     * Set warehouse code
     *
     * @param string $warehouseCode
     * @return $this
     */
    public function setWarehouseCode(string $warehouseCode): self;

    /**
     * Get warehouse name
     *
     * @return string|null
     */
    public function getWarehouseName(): ?string;

    /**
     * Set warehouse name
     *
     * @param string $warehouseName
     * @return $this
     */
    public function setWarehouseName(string $warehouseName): self;

    /**
     * Get price range filters
     *
     * @return \Pratech\Warehouse\Api\Data\PriceRangeInterface[]
     */
    public function getPriceRanges(): array;

    /**
     * Set price range filters
     *
     * @param \Pratech\Warehouse\Api\Data\PriceRangeInterface[] $priceRanges
     * @return $this
     */
    public function setPriceRanges(array $priceRanges): self;

    /**
     * Get category filters
     *
     * @return \Pratech\Warehouse\Api\Data\CategoryFilterInterface[]
     */
    public function getCategories(): array;

    /**
     * Set category filters
     *
     * @param \Pratech\Warehouse\Api\Data\CategoryFilterInterface[] $categories
     * @return $this
     */
    public function setCategories(array $categories): self;

    /**
     * Get attribute filters
     *
     * @return \Pratech\Warehouse\Api\Data\AttributeFilterInterface[]
     */
    public function getAttributes(): array;

    /**
     * Set attribute filters
     *
     * @param \Pratech\Warehouse\Api\Data\AttributeFilterInterface[] $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): self;
}
