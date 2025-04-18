<?php

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

class CategoryInfoCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
     * @param ProductsRedisCache $productsRedisCache
     * @param RedisCacheLogger $redisCacheLogger
     */
    public function __construct(
        private ProductsRedisCache $productsRedisCache,
        private RedisCacheLogger   $redisCacheLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $this->productsRedisCache->deleteCategoryInfo();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
