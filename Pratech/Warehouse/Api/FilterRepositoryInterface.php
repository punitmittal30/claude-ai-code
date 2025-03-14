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

use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Api\Data\FilterResultInterface;

/**
 * Interface for filter repository
 */
interface FilterRepositoryInterface
{
    /**
     * Get available filters for products in a warehouse
     *
     * @param string $warehouseCode Warehouse code
     * @param int|null $categoryId Optional category ID to filter by
     * @return FilterResultInterface
     * @throws LocalizedException
     */
    public function getWarehouseFilters(
        string $warehouseCode,
        ?int $categoryId = null
    );

    /**
     * Get available filters for products in the nearest dark store by pincode
     *
     * @param int $pincode Customer pincode to find nearest dark store
     * @param int|null $categoryId Optional category ID to filter by
     * @return FilterResultInterface
     * @throws LocalizedException
     */
    public function getDarkStoreFilters(
        int $pincode,
        ?int $categoryId = null
    );
}
