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

class StockItemChangeObserver implements ObserverInterface
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
     * Execute observer for stock changes
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $stockItem = $observer->getEvent()->getItem();

            if ($stockItem && $stockItem->getProductId()) {
                $productId = (int)$stockItem->getProductId();

                // If quantity or stock status has changed, clear dynamic cache
                if ($stockItem->getOrigData('qty') != $stockItem->getQty() ||
                    $stockItem->getOrigData('is_in_stock') != $stockItem->getIsInStock()
                ) {
                    $this->logger->info('Product stock changed, clearing dynamic cache', [
                        'product_id' => $productId,
                        'old_qty' => $stockItem->getOrigData('qty'),
                        'new_qty' => $stockItem->getQty(),
                        'old_status' => $stockItem->getOrigData('is_in_stock'),
                        'new_status' => $stockItem->getIsInStock()
                    ]);

                    // Clear only dynamic cache
                    $this->cacheService->clearProductDynamicCache($productId);
                    $this->attributeService->clearDynamicCache($productId);

                    // @todo: clear all category listing as well to reflect changes
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in StockItemChangeObserver: ' . $e->getMessage());
        }
    }
}
