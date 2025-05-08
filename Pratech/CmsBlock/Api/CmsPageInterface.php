<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cms Block Interface to power Cms APIs.
 */
interface CmsPageInterface
{
    /**
     * Retrieve Blog Categories.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getBlogCategories(): array;

    /**
     * Retrieve Blogs By Blog Category.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getBlogs(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array;

    /**
     * Retrieve block.
     *
     * @param string $identifier
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getBlogByIdentifier(string $identifier): array;

    /**
     * Retrieve Related Articles.
     *
     * @param string $identifier
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getRelatedArticles(string $identifier): array;

    /**
     * Retrieve Top Blogs.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getTopBlogs(): array;

    /**
     * Retrieve Top Blogs by Category.
     *
     * @param int $categoryId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getTopBlogsByCategory(int $categoryId = 0): array;

    /**
     * Retrieve New Blogs.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getNewBlogs(): array;
}
