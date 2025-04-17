<?php

namespace Hyuga\Catalog\Observer;

use Magento\Catalog\Model\Product;
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
        /** @var Product $product */
        $product = $observer->getEvent()->getData('product');
        try {
            $this->productsRedisCache->deleteProduct($product->getId());
            $this->productsRedisCache->deleteSearch();
            $this->productsRedisCache->deletePlp();
            $this->productsRedisCache->deleteProductsOffer($product->getId());
            $this->productsRedisCache->deleteBanner();
            $this->customerRedisCache->deleteAllCustomerPurchasedProducts();
        } catch (\Exception $exception) {
            $this->redisCacheLogger->error("execute() | UpdateStockProductCache | Product ID: " . $product->getId() .
                " cache clearing issue | " . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }

}
