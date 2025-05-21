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
use Magento\Review\Model\Review;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Observer to clear reviews cache
 */
class ReviewsCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
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
        /** @var Review $review */
        $review = $observer->getEvent()->getData('object');
        try {
            if ($review && $review->getEntityPkValue()) {
                $this->productsRedisCache->deleteProductReview($review->getEntityPkValue());
            }
            if ($review && $review->getCustomerId()) {
                $this->customerRedisCache->deleteCustomerWidget($review->getCustomerId());
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
