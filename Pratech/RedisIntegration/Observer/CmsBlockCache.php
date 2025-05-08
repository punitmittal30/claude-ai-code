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
use Magento\Cms\Model\Block;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\BlogsRedisCache;

/**
 * Update CMS Block Data in cache after cms block save.
 */
class CmsBlockCache implements ObserverInterface
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
        /** @var Block $block */
        $block = $observer->getEvent()->getData('object');
        try {
            $this->blogsRedisCache->deleteCmsBlock($block->getIdentifier());
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
