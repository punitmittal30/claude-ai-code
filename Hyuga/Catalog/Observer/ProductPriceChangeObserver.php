<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Observer;

use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Hyuga\Catalog\Service\GraphQlProductAttributeService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProductPriceChangeObserver implements ObserverInterface
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param GraphQlProductAttributeService $attributeService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private GraphQlProductAttributeService $attributeService,
        private LoggerInterface $logger
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
                    $this->logger->info('Product price changed, clearing dynamic cache', [
                        'product_id' => $productId
                    ]);

                    // Clear only dynamic cache
                    $this->cacheService->clearProductDynamicCache($productId);
                    $this->attributeService->clearDynamicCache($productId);

                    // @todo: clear all category listing as well to reflect changes
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in ProductPriceChangeObserver: ' . $e->getMessage());
        }
    }
}
