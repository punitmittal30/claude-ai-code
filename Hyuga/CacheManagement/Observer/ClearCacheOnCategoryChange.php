<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Observer;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Hyuga\CacheManagement\Api\NodeRedisServiceInterface;
use Hyuga\LogManagement\Logger\CachingLogger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ClearCacheOnCategoryChange implements ObserverInterface
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param CachingLogger $cachingLogger
     * @param NodeRedisServiceInterface $nodeRedisService
     */
    public function __construct(
        private CacheServiceInterface     $cacheService,
        private CachingLogger             $cachingLogger,
        private NodeRedisServiceInterface $nodeRedisService
    ) {
    }

    /**
     * Clear cache when dark store status changes.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $category = $observer->getEvent()->getData('category');
            $eventName = $observer->getEvent()->getName();

            switch ($eventName) {
                case 'catalog_category_delete_after':
                case 'catalog_category_save_after':
                    $this->cacheService->cleanCategoriesByPincodeCache($category->getId());
                    $this->cacheService->cleanSubcategoryCacheKey($category->getId());
                    break;
            }
        } catch (Exception $e) {
            $this->cachingLogger->error('Error clearing pincode cache in observer: ' . $e->getMessage());
        }
    }
}
