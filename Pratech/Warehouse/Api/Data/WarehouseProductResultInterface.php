<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Warehouse\Api\Data;

interface WarehouseProductResultInterface
{
    /**
     * Get warehouse code
     *
     * @return string|null
     */
    public function getWarehouseCode();

    /**
     * Set warehouse code
     *
     * @param string $warehouseCode
     * @return $this
     */
    public function setWarehouseCode(string $warehouseCode);

    /**
     * Get warehouse name
     *
     * @return string|null
     */
    public function getWarehouseName();

    /**
     * Set warehouse name
     *
     * @param string $warehouseName
     * @return $this
     */
    public function setWarehouseName(string $warehouseName);

    /**
     * Get category name
     *
     * @return string|null
     */
    public function getCategoryName();

    /**
     * Set category name
     *
     * @param string $categoryName
     * @return $this
     */
    public function setCategoryName(string $categoryName);

    /**
     * Get items
     *
     * @return array
     */
    public function getItems();

    /**
     * Set items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * Get total count
     *
     * @return int
     */
    public function getTotalCount();

    /**
     * Set total count
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount(int $totalCount);

    /**
     * Get available filters
     *
     * @return array
     */
    public function getAvailableFilters();

    /**
     * Set available filters
     *
     * @param array $filters
     * @return $this
     */
    public function setAvailableFilters(array $filters);
}
