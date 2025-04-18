<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Api;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Stock Management Interface to manage stock.
 */
interface StockManagementInterface
{
    /**
     * Update Stock Item By Product Sku
     *
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @param mixed $product
     * @return int
     * @throws NoSuchEntityException
     */
    public function updateStockItemBySku(string $productSku, StockItemInterface $stockItem, mixed $product = []): int;
}
