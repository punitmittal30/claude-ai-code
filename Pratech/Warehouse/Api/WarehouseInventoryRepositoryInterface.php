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

namespace Pratech\Warehouse\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Api\Data\WarehouseInventoryInterface;

interface WarehouseInventoryRepositoryInterface
{
    /**
     * Save inventory
     *
     * @param WarehouseInventoryInterface $inventory
     * @return WarehouseInventoryInterface
     */
    public function save(WarehouseInventoryInterface $inventory): WarehouseInventoryInterface;

    /**
     * Get inventory by ID
     *
     * @param int $inventoryId
     * @return WarehouseInventoryInterface
     */
    public function getById(int $inventoryId): WarehouseInventoryInterface;

    /**
     * Get inventory by warehouse ID and SKU
     *
     * @param string $warehouseCode
     * @param string $sku
     * @return WarehouseInventoryInterface
     */
    public function getByWarehouseSku(string $warehouseCode, string $sku): WarehouseInventoryInterface;

    /**
     * Get list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete inventory
     *
     * @param WarehouseInventoryInterface $inventory
     * @return bool
     */
    public function delete(WarehouseInventoryInterface $inventory): bool;

    /**
     * Delete by ID
     *
     * @param int $inventoryId
     * @return bool
     */
    public function deleteById(int $inventoryId): bool;

    /**
     * Update stock quantity
     *
     * @param string $warehouseCode
     * @param string $sku
     * @param int $quantity
     * @return WarehouseInventoryInterface
     */
    public function updateStock(string $warehouseCode, string $sku, int $quantity): WarehouseInventoryInterface;

    /**
     * Update inventory from vinculum.
     *
     * @param mixed $payload
     * @return array
     */
    public function updateInventory($payload): array;

    /**
     * Update inventory from vinculum.
     *
     * @param \Pratech\Warehouse\Api\Data\InventoryListInterface[] $Inventorylist
     * @return array
     * @throws LocalizedException
     */
    public function updateWarehouseInventory(array $Inventorylist): array;
}
