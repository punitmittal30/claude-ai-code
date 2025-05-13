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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Catalog\Helper\ConfigurableProduct;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Observer to update product stock status and cache on product stock item save after event.
 */
class StockItemSaveAfter implements ObserverInterface
{
    /**
     * @param Action                     $productAction
     * @param RedisCacheLogger           $redisCacheLogger
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableProduct        $configurableProductHelper
     * @param ProductsRedisCache         $productsRedisCache
     */
    public function __construct(
        private Action                     $productAction,
        private RedisCacheLogger           $redisCacheLogger,
        private ProductRepositoryInterface $productRepository,
        private ConfigurableProduct        $configurableProductHelper,
        private ProductsRedisCache         $productsRedisCache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $stockItem = $observer->getEvent()->getItem();
            $productId = $stockItem->getProductId();

            if (!$productId) {
                return;
            }

            $product = $this->productRepository->getById($productId);
            $currentStockStatus = $product->getCustomAttribute('item_stock_status')?->getValue();
            $newStockStatus = (int)$stockItem->getIsInStock();

            if ((int)$currentStockStatus !== $newStockStatus) {
                $this->productAction->updateAttributes(
                    [$productId],
                    ['item_stock_status' => $newStockStatus],
                    0
                );
                $this->configurableProductHelper->updateParentStock($product);
                $this->productsRedisCache->deleteProduct($stockItem->getProductId());
            }
            // $this->productsRedisCache->deletePlp();
            // $this->productsRedisCache->deleteSearch();
            $this->productsRedisCache->deleteExternalCatalog();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
