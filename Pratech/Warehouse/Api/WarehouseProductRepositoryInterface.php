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

namespace Pratech\Warehouse\Api;

use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

/**
 * Interface for retrieving products by warehouse
 */
interface WarehouseProductRepositoryInterface
{
    /**
     * Get product list available in a specific warehouse/dark store
     *
     * @param string $warehouseCode Warehouse code to filter products by
     * @param int $pageSize Number of products to return per page
     * @param int $currentPage Current page
     * @param string|null $sortField Field to sort by
     * @param string|null $sortDirection Direction to sort (ASC|DESC)
     * @param mixed $filters Array of filters to apply [field => [value, condition_type]]
     * @return \Pratech\Warehouse\Api\Data\WarehouseProductResultInterface
     */
    public function getProductsByWarehouse(
        string $warehouseCode,
        int $pageSize = 20,
        int $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        mixed $filters = []
    ): \Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

    /**
     * Get product list available in a dark store by pincode
     *
     * @param int $pincode Customer pincode to find nearest dark store
     * @param int $pageSize Number of products to return per page
     * @param int $currentPage Current page
     * @param string|null $sortField Field to sort by
     * @param string|null $sortDirection Direction to sort (ASC|DESC)
     * @param mixed $filters Array of filters to apply [field => [value, condition_type]]
     * @return \Pratech\Warehouse\Api\Data\WarehouseProductResultInterface
     */
    public function getDarkStoreProductsByPincode(
        int $pincode,
        int $pageSize = 20,
        int $currentPage = 1,
        ?string $sortField = null,
        ?string $sortDirection = 'ASC',
        mixed $filters = []
    ): \Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

    /**
     * Get product list in a specific category available in a dark store by pincode
     *
     * @param int $pincode Customer pincode to find nearest dark store
     * @param string $categorySlug Category slug to get products from
     * @param string $requestType
     * @return WarehouseProductResultInterface
     */
    public function getCategoryProductsByPincode(
        int $pincode,
        string $categorySlug,
        string $requestType = "carousel"
    ): \Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;
}
