<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\BlogsRedisCache;

/**
 * Observer to delete blog tags cache on update/delete of blog tags
 */
class BlogTagsCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
     * @param BlogsRedisCache $blogsRedisCache
     * @param RedisCacheLogger $redisCacheLogger
     */
    public function __construct(
        private BlogsRedisCache  $blogsRedisCache,
        private RedisCacheLogger $redisCacheLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $this->blogsRedisCache->deleteBlogTags();
            $this->blogsRedisCache->deleteBlogTagsByCategory();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
