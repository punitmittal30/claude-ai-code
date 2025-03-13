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

namespace Pratech\Warehouse\Api\Data;

interface WarehouseInventoryInterface
{
    public const INVENTORY_ID = 'inventory_id';
    public const WAREHOUSE_CODE = 'warehouse_code';
    public const SKU = 'sku';
    public const QUANTITY = 'quantity';

    /**
     * Get inventory ID
     *
     * @return int|null
     */
    public function getInventoryId(): ?int;

    /**
     * Set inventory ID
     *
     * @param int $inventoryId
     * @return $this
     */
    public function setInventoryId(int $inventoryId): static;

    /**
     * Get warehouse code
     *
     * @return string
     */
    public function getWarehouseCode(): string;

    /**
     * Set warehouse code
     *
     * @param string $warehouseCode
     * @return $this
     */
    public function setWarehouseCode(string $warehouseCode): static;

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): static;

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set quantity
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): static;
}
