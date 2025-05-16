<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Cron;

use Exception;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

class UpdateItemStockStatus
{
    /**
     * Is cron enabled for update item stock status.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/item_stock_status/status';

    /**
     * @param CronLogger $cronLogger
     * @param CollectionFactory $productCollectionFactory
     * @param Action $productAction
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private CronLogger           $cronLogger,
        private CollectionFactory    $productCollectionFactory,
        private Action               $productAction,
        private ResourceConnection   $resourceConnection,
        private ScopeConfigInterface $scopeConfig,
        private ProductsRedisCache   $productsRedisCache
    ) {
    }

    /**
     * Cron to update Item Stock Status product attribute.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateItemStockStatus cron started at ' . date('Y-m-d H:i:s'));
            try {
                $inStockProducts = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->joinField(
                        'qty',
                        'cataloginventory_stock_item',
                        'qty',
                        'product_id=entity_id',
                    )->getSelect()->where('is_in_stock = 1')
                    ->reset(Select::COLUMNS)->columns('entity_id');

                $outOfStockProducts = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('entity_id')
                    ->joinField(
                        'qty',
                        'cataloginventory_stock_item',
                        'qty',
                        'product_id=entity_id',
                    )->getSelect()->where('is_in_stock = 0')
                    ->reset(Select::COLUMNS)->columns('entity_id');

                $inStock = $this->resourceConnection->getConnection()->fetchCol($inStockProducts);
                $outOfStock = $this->resourceConnection->getConnection()->fetchCol($outOfStockProducts);

                $this->productAction->updateAttributes($inStock, ['item_stock_status' => 1], 0);
                $this->productAction->updateAttributes($outOfStock, ['item_stock_status' => 0], 0);
                $this->productsRedisCache->deleteSearch();
                $this->productsRedisCache->deletePlp();
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateItemStockStatus cron ended at ' . date('Y-m-d H:i:s'));
        }
    }
}
