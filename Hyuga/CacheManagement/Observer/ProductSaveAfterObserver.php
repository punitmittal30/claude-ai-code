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
use Hyuga\LogManagement\Logger\CachingLogger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param CachingLogger $cachingLogger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private CachingLogger         $cachingLogger
    ) {
    }

    /**
     * Execute observer for product price changes
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();

            if ($product && $product->getId()) {
                $productId = (int)$product->getId();

                // If price or special price has changed, clear dynamic cache
                if ($product->dataHasChangedFor('price') ||
                    $product->dataHasChangedFor('special_price') ||
                    $product->dataHasChangedFor('special_from_date') ||
                    $product->dataHasChangedFor('special_to_date')
                ) {
                    $this->cachingLogger->info('Product price changed, clearing dynamic cache', [
                        'product_id' => $productId
                    ]);

                    // Clear only dynamic cache
                    $this->cacheService->clearProductDynamicAttributesCache($productId);
                    $this->cacheService->clearAllDarkStoreCarouselProductsCache();
                }
            }
        } catch (Exception $e) {
            $this->cachingLogger->error('Error in ProductPriceChangeObserver: ' . $e->getMessage());
        }
    }
}
