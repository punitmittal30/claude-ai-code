<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Cron;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\RedisIntegration\Model\ProductsRedisCache;
use Pratech\Search\Model\ResourceModel\QtySold;

/**
 * Cron to update quantity sold for product.
 */
class UpdateProductQtySold
{
    /**
     * Sort By Bestseller Config
     */
    public const IS_SORT_BY_BESTSELLER_ENABLED = 'search/sort_by_bestseller/enable';

    /**
     * N Days Constant
     */
    public const N_DAYS = 'search/sort_by_bestseller/bestseller_days';

    /**
     * Update Product Qty Sold Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CronLogger $cronLogger
     * @param QtySold $qtySoldResource
     * @param CollectionFactory $productCollectionFactory
     * @param Configurable $configurable
     * @param ProductRepositoryInterface $productRepository
     * @param Action $productActionResource
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        private ScopeConfigInterface       $scopeConfig,
        private CronLogger                 $cronLogger,
        private QtySold                    $qtySoldResource,
        private CollectionFactory          $productCollectionFactory,
        private Configurable               $configurable,
        private ProductRepositoryInterface $productRepository,
        private Action                     $productActionResource,
        protected TypeListInterface        $cacheTypeList,
        protected Pool                     $cacheFrontendPool,
        private ProductsRedisCache         $productsRedisCache
    ) {
    }

    /**
     * Execute Function
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_SORT_BY_BESTSELLER_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateProductQtySold cron started at ' . date('Y-m-d H:i:s'));
            try {
                // $this->resetQtySold();
                $nDays = $this->scopeConfig->getValue(self::N_DAYS, ScopeInterface::SCOPE_STORE);
                $daysCriteria = $this->getCurrentDateMinusNDays($nDays);
                $productsSoldOverNDays = $this->qtySoldResource->getQtySold($daysCriteria);
                foreach ($productsSoldOverNDays as $productSoldOverNDays) {
                    $this->productActionResource->updateAttributes(
                        [$productSoldOverNDays['product_id']],
                        ['qty_sold' => $productSoldOverNDays['qty_ordered']],
                        0
                    );
                    $parentId = $this->getParentProductId($productSoldOverNDays['product_id']);
                    if ($parentId) {
                        $this->updateParentProductQtySold($parentId);
                    }
                }
                $this->productsRedisCache->deletePlp();
                $this->productsRedisCache->deleteSearch();
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateProductQtySold cron ended at ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Get Current Date
     *
     * @param string $nDays
     * @return string
     */
    public function getCurrentDateMinusNDays(string $nDays): string
    {
        $today = date('Y-m-d');
        return date("Y-m-d", strtotime('-' . $nDays . 'days', strtotime($today)));
    }

    /**
     * Get Parent Product ID By Child ID
     *
     * @param int $childId
     * @return mixed|string
     */
    private function getParentProductId(int $childId): mixed
    {
        $product = $this->configurable->getParentIdsByChild($childId);
        if (isset($product[0])) {
            return $product[0];
        }
        return "";
    }

    /**
     * Update Parent Product Qty Sold Attribute Value.
     *
     * @param int $parentId
     * @return void
     */
    private function updateParentProductQtySold(int $parentId): void
    {
        try {
            $parentProduct = $this->productRepository->getById($parentId);
            $parentProductQtySold = $parentProduct->getCustomAttribute('qty_sold')
                ? $parentProduct->getCustomAttribute('qty_sold')->getValue()
                : 0;
            if ($parentProductQtySold == "0") {
                $totalQtyOrdered = 0;
                $childProductIds = $this->getChildProductIds($parentProduct);
                foreach ($childProductIds as $childProductId) {
                    $childProduct = $this->productRepository->getById($childProductId);
                    $childProductQtySold = $childProduct->getCustomAttribute('qty_sold')
                        ? $childProduct->getCustomAttribute('qty_sold')->getValue()
                        : 0;
                    $totalQtyOrdered = $totalQtyOrdered + $childProductQtySold;
                }
                $this->productActionResource->updateAttributes(
                    [$parentId],
                    ['qty_sold' => $totalQtyOrdered],
                    0
                );
            }
            $this->flushCache();
        } catch (Exception|NoSuchEntityException $e) {
            $this->cronLogger->error($e->getMessage() . __METHOD__);
            $this->flushCache();
        }
    }

    /**
     * Get Child Product IDs for Configurable Products
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getChildProductIds(ProductInterface $product): array
    {
        return array_values($product->getExtensionAttributes()->getConfigurableProductLinks());
    }

    /**
     * Flush Magento Cache.
     *
     * @return void
     */
    private function flushCache(): void
    {
        $types = ['config', 'layout', 'block_html', 'collections', 'reflection',
            'db_ddl', 'eav', 'config_integration', 'config_integration_api',
            'full_page', 'config_webservice'];

        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Reset Products Qty Sold To Zero
     *
     * @return void
     * @throws Exception
     */
    private function resetQtySold(): void
    {
        $productIds = $this->productCollectionFactory->create()->getAllIds();
        $this->productActionResource->updateAttributes(
            $productIds,
            ['qty_sold' => 0],
            0
        );
    }
}
