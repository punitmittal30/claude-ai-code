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
 * Observer to clear category cache.
 */
class CategoryPositionCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
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
            $this->productsRedisCache->deleteCategoryMapping();
            $this->productsRedisCache->deleteMenu();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
