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
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Observer to clear product cache on bulk product update.
 */
class MirasvitBulkProductOperation implements ObserverInterface
{
    /**
     * @param RedisCacheLogger $redisCacheLogger
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private RedisCacheLogger   $redisCacheLogger,
        private ProductsRedisCache $productsRedisCache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $productIds = $observer->getEvent()->getData('product_ids');
            foreach ($productIds as $productId) {
                $this->productsRedisCache->deleteProduct($productId);
            }
            $this->productsRedisCache->deleteSearch();
            $this->productsRedisCache->deletePlp();
            $this->productsRedisCache->deleteBanner();
            $this->productsRedisCache->deleteExternalCatalog();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
