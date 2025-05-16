<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Blog\Api\CategoryRepositoryInterface;
use Pratech\Blog\Api\Data\CategoryInterface;
use Pratech\Blog\Api\Data\CategoryInterfaceFactory;
use Pratech\Blog\Api\Data\CategorySearchResultsInterfaceFactory;
use Pratech\Blog\Model\ResourceModel\Category as ResourceCategory;
use Pratech\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class CategoryRepository implements CategoryRepositoryInterface
{

    /**
     * @var ResourceCategory
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var Category
     */
    protected $searchResultsFactory;

    /**
     * @var CategoryInterfaceFactory
     */
    protected $categoryFactory;

    /**
     * @param ResourceCategory $resource
     * @param CategoryInterfaceFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceCategory $resource,
        CategoryInterfaceFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategorySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(CategoryInterface $category)
    {
        try {
            $this->resource->save($category);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the category: %1',
                $exception->getMessage()
            ));
        }
        return $category;
    }

    /**
     * @inheritDoc
     */
    public function get($categoryId)
    {
        $category = $this->categoryFactory->create();
        $this->resource->load($category, $categoryId);
        if (!$category->getId()) {
            throw new NoSuchEntityException(__('Category with id "%1" does not exist.', $categoryId));
        }
        return $category;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->categoryCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(CategoryInterface $category)
    {
        try {
            $categoryModel = $this->categoryFactory->create();
            $this->resource->load($categoryModel, $category->getCategoryId());
            $this->resource->delete($categoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Category: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($categoryId)
    {
        return $this->delete($this->get($categoryId));
    }

    /**
     * @inheritDoc
     */
    public function getCategories()
    {
        $categories = $this->categoryCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => 1])
            ->setOrder('sort_order', 'asc');
        return $categories;
    }

    /**
     * Function to get category by url_key
     *
     * @param string $categoryUrlKey
     */
    public function getByUrlKey(string $categoryUrlKey)
    {
        $category = $this->categoryCollectionFactory->create()
            ->addFieldToFilter('url_key', ['eq' => $categoryUrlKey])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(__('Blog category with id "%1" does not exist.', $categoryId));
        }
        return $category;
    }
}
