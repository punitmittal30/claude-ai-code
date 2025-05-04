<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mirasvit\Search\Model\ScoreRule\Indexer\ScoreRuleIndexer;
use Mirasvit\Search\Repository\IndexRepository;
use Mirasvit\Search\Repository\ScoreRuleRepository;
use Pratech\Base\Logger\CronLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

class UpdateOutOfStockProductsForSearch
{
    /**
     * Is cron enabled for update out of stock products for search.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/update_oos_for_search/status';

    /**
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param ScoreRuleIndexer $scoreRuleIndexer
     * @param ScoreRuleRepository $scoreRuleRepository
     * @param IndexRepository $indexRepository
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private CronLogger           $cronLogger,
        private ScopeConfigInterface $scopeConfig,
        private ScoreRuleIndexer     $scoreRuleIndexer,
        private ScoreRuleRepository  $scoreRuleRepository,
        private IndexRepository      $indexRepository,
        private ProductsRedisCache   $productsRedisCache
    ) {
    }

    /**
     * Cron to update out of stock products for search.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateOutOfStockProductsForSearch cron started at ' . date('Y-m-d H:i:s'));
            try {
                $scoreRule = $this->scoreRuleRepository->getCollection()
                    ->addFieldToFilter('title', 'OOS');
                if (!empty($scoreRule->getFirstItem())) {
                    $this->scoreRuleIndexer->execute($scoreRule->getFirstItem(), []);
                    $this->indexRepository->getInstanceByIdentifier('catalogsearch_fulltext')
                        ->reindexAll();

                    $this->productsRedisCache->deleteSearch();
                    $this->productsRedisCache->deletePlp();
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateOutOfStockProductsForSearch cron ended at ' . date('Y-m-d H:i:s'));
        }
    }
}
