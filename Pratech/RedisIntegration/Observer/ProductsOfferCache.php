<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
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
 * Observer to update products offer cache on sales rule save after event.
 */
class ProductsOfferCache implements ObserverInterface
{
    /**
     * Update Products Offer Cache Constructor
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
            $this->productsRedisCache->deleteAllProductsOffer();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
