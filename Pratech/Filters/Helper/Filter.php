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

use Hyuga\Catalog\Model\Repository\CategoryRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Logger\Logger;
use Pratech\Filters\Model\FiltersPositionFactory;
use Pratech\Filters\Model\QuickFiltersFactory;

/**
 * Product helper class to provide data to catalog api endpoints.
 */
class Filter
{
    /**
     * Filter Helper Constructor
     *
     * @param Logger $logger
     * @param FiltersPositionFactory $filtersPositionFactory
     * @param QuickFiltersFactory $quickFiltersFactory
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        private Logger                 $logger,
        private FiltersPositionFactory $filtersPositionFactory,
        private QuickFiltersFactory    $quickFiltersFactory,
        private CategoryRepository     $categoryRepository
    ) {
    }

    /**
     * Get Filters Position Data
     *
     * @return array
     */
    public function getFiltersPosition(): array
    {
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
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Filters Position Data
     *
     * @param int $categoryId
     * @return array
     */
    public function getQuickFilters(int $categoryId): array
    {
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
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get All Quick Filters Data.
     *
     * @return array
     */
    public function getAllQuickFilters(): array
    {
        $result = [];
        try {
            $collection = $this->quickFiltersFactory->create()
                ->getCollection();

            $categoryIdSlugMapping = $this->categoryRepository->getMapping();

            foreach ($collection as $filter) {
                $categorySlug = $categoryIdSlugMapping['map_by_id'][$filter->getCategoryId()];
                $categoryFilters = json_decode($filter->getFiltersData(), true);
                $result[$categorySlug] = $this->formatFiltersData($categoryFilters);
            }
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
            $formattedFilter = [
                'key' => $filter['attribute_type'],
                'header' => $filter['attribute_label'],
                'value' => []
            ];

            foreach ($filter['attribute_value'] as $value) {
                $formattedFilter['value'][] = [
                    'id' => $value['value'],
                    'label' => $value['label']
                ];
            }
            $formattedData[] = $formattedFilter;
        }
        return $formattedData;
    }
}
