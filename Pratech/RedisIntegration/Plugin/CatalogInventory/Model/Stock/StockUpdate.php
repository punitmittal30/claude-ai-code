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

namespace Pratech\RedisIntegration\Plugin\CatalogInventory\Model\Stock;

use Exception;
use Magento\CatalogInventory\Model\Stock\Item;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

/**
 * Stock update after plugin to update product cache once product goes out of stock.
 */
class StockUpdate
{
    /**
     * Stock Update Constructor
     *
     * @param RedisCacheLogger $redisCacheLogger
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private RedisCacheLogger   $redisCacheLogger,
        private ProductsRedisCache $productsRedisCache
    ) {
    }

    /**
     * After Set Is In Stock Method
     *
     * @param Item $subject
     * @param Item $result
     * @return Item
     */
    public function afterSetIsInStock(Item $subject, Item $result): Item
    {
        try {
            if ($subject->getProductId()) {
                $this->productsRedisCache->deleteProduct($subject->getProductId());
            }
            $this->productsRedisCache->deletePlp();
            $this->productsRedisCache->deleteSearch();
            return $result;
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__ . " | " . $exception->getLine());
        }
        return $result;
    }
}
