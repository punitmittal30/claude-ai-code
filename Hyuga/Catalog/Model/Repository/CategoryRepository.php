<?php

namespace Hyuga\Catalog\Model\Repository;

use Exception;
use Hyuga\Catalog\Api\CategoryRepositoryInterface;
use Hyuga\CustomLogging\Logger\CachingLogger;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
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
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param CachingLogger $cachingLogger
     */
    public function __construct(
        private Response                  $response,
        private CategoryCollectionFactory $categoryCollectionFactory,
        private CacheInterface            $cache,
        private SerializerInterface       $serializer,
        private CachingLogger             $cachingLogger
    )
    {
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
        $cacheKey = 'category_id_slug_mapping';

        // Try to get from cache first
        $cachedMapping = $this->cache->load($cacheKey);
        if ($cachedMapping) {
            return $this->serializer->unserialize($cachedMapping);
        }

        // If not in cache, build the mapping
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

            $mapping = [
                "map_by_id" => $mapById,
                "map_by_slug" => $mapByKey
            ];

            // Save to cache with tags that will be cleared on category changes
            $this->cache->save(
                $this->serializer->serialize($mapping),
                $cacheKey,
                ['category_mapping_cache', 'catalog_category', 'catalog_url'],
                604800 // 1 week
            );
            return $mapping;
        } catch (Exception $e) {
            $this->cachingLogger->error('Error building category mapping: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to build category mapping: %1', $e->getMessage()));
        }
    }
}
