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

interface TagRepositoryInterface
{
    /**
     * Save Tag
     *
     * @param \Pratech\Blog\Api\Data\TagInterface $tag
     * @return \Pratech\Blog\Api\Data\TagInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Pratech\Blog\Api\Data\TagInterface $tag);

    /**
     * Retrieve Tag
     *
     * @param string $tagId
     * @return \Pratech\Blog\Api\Data\TagInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($tagId);

    /**
     * Retrieve Tag matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Blog\Api\Data\TagSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Tag
     *
     * @param \Pratech\Blog\Api\Data\TagInterface $tag
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Pratech\Blog\Api\Data\TagInterface $tag);

    /**
     * Delete Tag by ID
     *
     * @param string $tagId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($tagId);

    /**
     * Retrieve Blog Tags.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getBlogTags(): array;

    /**
     * Retrieve Blog Tags by category.
     *
     * @param int $categoryId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getBlogTagsByCategory(int $categoryId = 0): array;

    /**
     * Retrieve blogs by Tag url_key.
     *
     * @param string $tagUrlKey
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getTaggedBlogs(string $tagUrlKey): array;

    /**
     * Retrieve blogs by Tag url_key.
     *
     * @param string $tagUrlKey
     * @param int $categoryId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getTaggedBlogsByCategory(string $tagUrlKey, int $categoryId = 0): array;
}
