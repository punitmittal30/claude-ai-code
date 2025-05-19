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
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Observer to update cache of category and products assigned to it.
 */
class CategoryCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
     * @param Logger $apiLogger
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private Logger             $apiLogger,
        private ProductsRedisCache $productsRedisCache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getData('category');
        $categorySlug = $category->getUrlKey();

        $productIds = $observer->getEvent()->getData('product_ids');
        try {
            $this->productsRedisCache->deletePlp($categorySlug);
            foreach ($productIds as $productId) {
                $this->productsRedisCache->deleteProduct($productId);
            }
            $this->productsRedisCache->deleteExternalCatalog();
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
