<?php

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
