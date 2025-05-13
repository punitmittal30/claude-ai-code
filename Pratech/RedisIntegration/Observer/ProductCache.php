<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Observer to update product cache and associated categories on product save after event.
 */
class ProductCache implements ObserverInterface
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
            // $this->productsRedisCache->deleteSearch();
            // $this->productsRedisCache->deletePlp();
            $this->productsRedisCache->deleteProductsOffer($product->getId());
            $this->productsRedisCache->deleteBanner();
            $this->customerRedisCache->deleteAllCustomerPurchasedProducts();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("execute() | ProductCache | Product ID: " . $product->getId() .
                " cache clearing issue | " . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }
}
