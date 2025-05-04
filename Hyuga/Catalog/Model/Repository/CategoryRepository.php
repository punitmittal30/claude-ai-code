<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Model\Repository;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Hyuga\Catalog\Api\CategoryRepositoryInterface;
use Hyuga\LogManagement\Logger\CachingLogger;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Base\Model\Data\Response;

/**
 * Category class to expose categories endpoint
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Constant for CATEGORY API RESOURCE
     */
    public const CATEGORY_API_RESOURCE = 'category';

    /**
     * Category Constructor
     *
     * @param Response $response
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CachingLogger $cachingLogger
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(
        private Response                  $response,
        private CategoryCollectionFactory $categoryCollectionFactory,
        private CachingLogger             $cachingLogger,
        private CacheServiceInterface     $cacheService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCategoryIdSlugMapping(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->getMapping()
        );
    }

    /**
     * Get mapping data.
     *
     * @return array[]
     * @throws LocalizedException
     */
    public function getMapping(): array
    {
        $cacheKey = CacheServiceInterface::CACHE_KEY_CATEGORY_ID_SLUG_MAPPING;
        $cachedResult = $this->cacheService->get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $mapById = [];
        $mapByKey = [];

        try {
            /** @var Category $category */
            $categories = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect(['entity_id', 'url_key'])
                ->addAttributeToFilter('is_active', ['eq' => 1])
                ->getItems();

            foreach ($categories as $category) {
                if ($category->getUrlKey()) {
                    $mapById[$category->getId()] = $category->getUrlKey();
                    $mapByKey[$category->getUrlKey()] = $category->getId();
                }
            }

            $result = [
                "map_by_id" => $mapById,
                "map_by_slug" => $mapByKey
            ];

            $this->cacheService->save(
                $cacheKey,
                $result,
                ['category_mapping_cache', 'catalog_category', 'catalog_url'],
                CacheServiceInterface::CACHE_LIFETIME_1_WEEK
            );
            return $result;
        } catch (Exception $e) {
            $this->cachingLogger->error('Error building category mapping: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to build category mapping: %1', $e->getMessage()));
        }
    }
}
