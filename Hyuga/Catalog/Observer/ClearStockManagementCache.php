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

namespace Hyuga\Catalog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

class ClearStockManagementCache implements ObserverInterface
{
    /**
     * Update Product Cache Constructor
     *
     * @param RedisCacheLogger $redisCacheLogger
     * @param ProductsRedisCache $productsRedisCache
     * @param CustomerRedisCache $customerRedisCache
     */
    public function __construct(
        private RedisCacheLogger   $redisCacheLogger,
        private ProductsRedisCache $productsRedisCache,
        private CustomerRedisCache $customerRedisCache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getData('product');
        $attributeChanges = $observer->getEvent()->getData('attribute_changes') ?: [];
        $stockStatusChanged = $observer->getEvent()->getData('stock_status_changed') ?: false;
        $productId = $product->getId();

        try {
            $priceChanged = isset($attributeChanges['price']);
            $expiryDateChanged = isset($attributeChanges['expiry_date']);

            if ($priceChanged || $stockStatusChanged) {
                $this->productsRedisCache->deleteProduct($productId);
                $this->productsRedisCache->deleteSearch();
                $this->productsRedisCache->deletePlp();
                $this->productsRedisCache->deleteProductsOffer($productId);
                $this->productsRedisCache->deleteBanner();
                $this->customerRedisCache->deleteAllCustomerPurchasedProducts();
            } elseif ($expiryDateChanged) {
                $this->productsRedisCache->deleteProduct($productId);
            }

        } catch (\Exception $exception) {
            $this->redisCacheLogger->error("execute() | UpdateStockProductCache | Product ID: " . $productId .
                " cache clearing issue | " . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }
}
