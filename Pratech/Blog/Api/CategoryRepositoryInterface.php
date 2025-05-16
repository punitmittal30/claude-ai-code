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

namespace Pratech\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Pratech\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;

interface CategoryRepositoryInterface
{
    /**
     * Save Category
     *
     * @param \Pratech\Blog\Api\Data\CategoryInterface $category
     * @return \Pratech\Blog\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Pratech\Blog\Api\Data\CategoryInterface $category
    );

    /**
     * Retrieve Category
     *
     * @param string $categoryId
     * @return \Pratech\Blog\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($categoryId);

    /**
     * Retrieve Category matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Blog\Api\Data\CategorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Category
     *
     * @param \Pratech\Blog\Api\Data\CategoryInterface $category
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Pratech\Blog\Api\Data\CategoryInterface $category
    );

    /**
     * Delete Category by ID
     *
     * @param string $categoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($categoryId);

    /**
     * Retrieve blog categories
     *
     * @return CategoryCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getCategories();
}
