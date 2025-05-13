<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Plugin\Model\Export\RowCustomizer;

use Exception;
use Amasty\Feed\Model\Export\Product as ExportProduct;
use Amasty\Feed\Model\Export\RowCustomizer\Category as FeedCategory;
use Amasty\Feed\Model\Category\Repository;
use Pratech\Base\Logger\Logger;
use Amasty\Feed\Model\Category\ResourceModel\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Plugin to Update Category for feed
 */
class Category extends FeedCategory
{

    /**
     * Feed Category Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ExportProduct                              $export
     * @param CollectionFactory                          $feedCategoryCollectionFactory
     * @param Repository                                 $repository
     * @param CategoryCollectionFactory                  $categoryCollectionFactory
     * @param CategoryRepositoryInterface                $categoryRepository
     * @param Logger                                     $logger
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ExportProduct $export,
        CollectionFactory $feedCategoryCollectionFactory,
        Repository $repository,
        private CategoryCollectionFactory   $categoryCollectionFactory,
        private CategoryRepositoryInterface $categoryRepository,
        private Logger $logger
    ) {
        parent::__construct($storeManager, $export, $feedCategoryCollectionFactory, $repository);
    }

    /**
     * {@inheritdoc}
     */
    public function addData($dataRow, $productId)
    {
        $result = parent::addData($dataRow, $productId);
        $categoryData = &$result['amasty_custom_data']['category'];
        if (isset($this->_rowCategories[$productId])) {
            $categories = $this->getProductCategory($this->_rowCategories[$productId]);
            $idx = 1;
            foreach ($categories as $category) {
                $categoryData['category'.$idx++] =  str_replace('&', '&amp;', $category);
            }
        }
        return $result;
    }

    /**
     * Get Category Assignment for products
     *
     * @param  array $categoryIds
     * @return array|null
     */
    public function getProductCategory(array $categoryIds): ?array
    {
        try {
            $categoryPath = [];
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                $mainCategoryParentId = $this->getCategoryIdByUrl('categories');
                if ($key = array_keys($category->getPathIds(), $mainCategoryParentId)) {
                    $categoryName = "";
                    $pathIds = array_slice($category->getPathIds(), (int)$key[0] + 1);
                    $categoryCollection = $this->categoryCollectionFactory->create()
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('entity_id')
                        ->addAttributeToFilter('entity_id', ['in' => $pathIds]);
                     $categoryName = $categoryCollection->getLastItem()->getName();
                    if ($categoryName) {
                        $categoryPath[$category->getId()] = $categoryName;
                    }
                }
            }
            return $categoryPath;
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
            return null;
        }
    }

    /**
     * Get Category ID By URL
     *
     * @param  string $urlKey
     * @return int
     * @throws LocalizedException
     */
    private function getCategoryIdByUrl(string $urlKey): int
    {
        $category = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('url_key', $urlKey)->getData()[0];

        return $category['entity_id'];
    }
}
