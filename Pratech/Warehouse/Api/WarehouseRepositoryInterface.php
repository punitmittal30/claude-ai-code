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
use Pratech\Warehouse\Api\Data\WarehouseInterface;

interface WarehouseRepositoryInterface
{
    /**
     * Save warehouse.
     *
     * @param WarehouseInterface $warehouse
     * @return WarehouseInterface
     */
    public function save(WarehouseInterface $warehouse): WarehouseInterface;

    /**
     * Get warehouse by ID
     *
     * @param int $warehouseId
     * @return WarehouseInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $warehouseId): WarehouseInterface;

    /**
     * Get warehouse by Code
     *
     * @param string $warehouseCode
     * @return WarehouseInterface
     */
    public function getByCode(string $warehouseCode): WarehouseInterface;

    /**
     * Retrieve warehouses matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete warehouse
     *
     * @param WarehouseInterface $warehouse
     * @return bool
     */
    public function delete(WarehouseInterface $warehouse): bool;

    /**
     * Delete warehouse by ID
     *
     * @param int $warehouseId
     * @return bool
     */
    public function deleteById(int $warehouseId): bool;

    /**
     * Get Dark Store Page.
     *
     * @return array
     */
    public function getDarkStores(): array;

    /**
     * Get Available Dark Store Page.
     *
     * @return array
     */
    public function getAvailableDarkStores(): array;
}
