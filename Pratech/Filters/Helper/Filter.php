<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Helper;

use Exception;
use Hyuga\Catalog\Model\Repository\CategoryRepository;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Base\Logger\Logger;
use Pratech\Filters\Model\FiltersPositionFactory;
use Pratech\Filters\Model\QuickFiltersFactory;

/**
 * Product helper class to provide data to catalog api endpoints.
 */
class Filter
{
    /**
     * Cache identifier for all quick filters
     */
    private const CACHE_ID_ALL_QUICK_FILTERS = 'pratech_all_quick_filters';

    /**
     * Cache identifier for filters position
     */
    private const CACHE_ID_FILTERS_POSITION = 'pratech_filters_position';

    /**
     * Cache tag for quick filters
     */
    private const CACHE_TAG_QUICK_FILTERS = 'PRATECH_QUICK_FILTERS';

    /**
     * Cache tag for filters position
     */
    private const CACHE_TAG_FILTERS_POSITION = 'PRATECH_FILTERS_POSITION';

    /**
     * Cache lifetime in seconds (1 hour)
     */
    private const CACHE_LIFETIME = 3600;

    /**
     * Filter Helper Constructor
     *
     * @param Logger $logger
     * @param FiltersPositionFactory $filtersPositionFactory
     * @param QuickFiltersFactory $quickFiltersFactory
     * @param CategoryRepository $categoryRepository
     * @param CacheInterface $cache
     * @param Json $serializer
     */
    public function __construct(
        private Logger                 $logger,
        private FiltersPositionFactory $filtersPositionFactory,
        private QuickFiltersFactory    $quickFiltersFactory,
        private CategoryRepository     $categoryRepository,
        private CacheInterface         $cache,
        private Json                   $serializer
    ) {
    }

    /**
     * Get Filters Position Data
     *
     * @param bool $forceReload Force reload data from DB
     * @return array
     */
    public function getFiltersPosition(bool $forceReload = false): array
    {
        if (!$forceReload) {
            $cachedData = $this->getCachedData(self::CACHE_ID_FILTERS_POSITION);
            if ($cachedData !== false) {
                return $cachedData;
            }
        }

        $result = [];
        try {
            $collection = $this->filtersPositionFactory->create()
                ->getCollection()
                ->setOrder('position', 'ASC');

            foreach ($collection as $filter) {
                $result[] = [
                    "attribute_id" => $filter->getAttributeId(),
                    "attribute_code" => $filter->getAttributeCode(),
                    "attribute_name" => $filter->getAttributeName(),
                    "position" => $filter->getPosition(),
                ];
            }

            $this->saveDataToCache(
                $result,
                self::CACHE_ID_FILTERS_POSITION,
                [self::CACHE_TAG_FILTERS_POSITION, Block::CACHE_TAG]
            );
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get cached data
     *
     * @param string $cacheId
     * @return array|false
     */
    private function getCachedData(string $cacheId)
    {
        $cachedData = $this->cache->load($cacheId);
        if ($cachedData) {
            try {
                return $this->serializer->unserialize($cachedData);
            } catch (Exception $e) {
                $this->logger->error('Failed to un-serialize cached data: ' . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * Save data to cache
     *
     * @param array $data
     * @param string $cacheId
     * @param array $cacheTags
     * @return void
     */
    private function saveDataToCache(array $data, string $cacheId, array $cacheTags): void
    {
        try {
            $serializedData = $this->serializer->serialize($data);
            $this->cache->save(
                $serializedData,
                $cacheId,
                $cacheTags,
                self::CACHE_LIFETIME
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to cache data: ' . $e->getMessage());
        }
    }

    /**
     * Get Filters Position Data
     *
     * @param int $categoryId
     * @param bool $forceReload Force reload data from DB
     * @return array
     */
    public function getQuickFilters(int $categoryId, bool $forceReload = false): array
    {
        $cacheId = 'pratech_quick_filters_' . $categoryId;

        if (!$forceReload) {
            $cachedData = $this->getCachedData($cacheId);
            if ($cachedData !== false) {
                return $cachedData;
            }
        }

        $result = [];
        try {
            $collection = $this->quickFiltersFactory->create()
                ->getCollection()
                ->addFieldToFilter('category_id', $categoryId);

            foreach ($collection as $filter) {
                $result = [
                    "quick_filters" => $filter->getFiltersData() ?
                        json_decode($filter->getFiltersData(), true)
                        : [],
                ];
            }

            $this->saveDataToCache($result, $cacheId, [self::CACHE_TAG_QUICK_FILTERS, Block::CACHE_TAG]);
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get All Quick Filters Data.
     *
     * @param bool $forceReload Force reload data from DB
     * @return array
     */
    public function getAllQuickFilters(bool $forceReload = false): array
    {
        if (!$forceReload) {
            $cachedData = $this->getCachedData(self::CACHE_ID_ALL_QUICK_FILTERS);
            if ($cachedData !== false) {
                return $cachedData;
            }
        }

        $result = [];
        try {
            $collection = $this->quickFiltersFactory->create()
                ->getCollection();

            $categoryIdSlugMapping = $this->categoryRepository->getMapping();

            foreach ($collection as $filter) {
                if (!isset($categoryIdSlugMapping['map_by_id'][$filter->getCategoryId()])) {
                    continue; // Skip if category mapping doesn't exist
                }

                $categorySlug = $categoryIdSlugMapping['map_by_id'][$filter->getCategoryId()];
                $filtersData = $filter->getFiltersData();

                if (!empty($filtersData)) {
                    $categoryFilters = json_decode($filtersData, true);
                    $result[$categorySlug] = $this->formatFiltersData($categoryFilters);
                }
            }

            $this->saveDataToCache(
                $result,
                self::CACHE_ID_ALL_QUICK_FILTERS,
                [self::CACHE_TAG_QUICK_FILTERS, Block::CACHE_TAG]
            );
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Format filters data to ensure proper structure
     *
     * @param array $filtersData
     * @return array
     */
    private function formatFiltersData(array $filtersData): array
    {
        $formattedData = [];

        foreach ($filtersData as $filter) {
            if (!isset($filter['attribute_type'], $filter['attribute_label'], $filter['attribute_value']) ||
                !is_array($filter['attribute_value'])) {
                continue;
            }

            $formattedFilter = [
                'key' => $filter['attribute_type'],
                'header' => $filter['attribute_label'],
                'value' => []
            ];

            foreach ($filter['attribute_value'] as $value) {
                if (isset($value['value'], $value['label'])) {
                    $formattedFilter['value'][] = [
                        'id' => $value['value'],
                        'label' => $value['label']
                    ];
                }
            }

            $formattedData[] = $formattedFilter;
        }
        return $formattedData;
    }

    /**
     * Clean quick filters cache
     *
     * @return void
     */
    public function cleanQuickFiltersCache(): void
    {
        $this->cache->clean([self::CACHE_TAG_QUICK_FILTERS]);
    }

    /**
     * Clean filters position cache
     *
     * @return void
     */
    public function cleanFiltersPositionCache(): void
    {
        $this->cache->clean([self::CACHE_TAG_FILTERS_POSITION]);
    }

    /**
     * Clean all filters cache
     *
     * @return void
     */
    public function cleanAllFiltersCache(): void
    {
        $this->cache->clean([self::CACHE_TAG_QUICK_FILTERS, self::CACHE_TAG_FILTERS_POSITION]);
    }
}
