<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Model;

use Predis\Client;

/**
 * Redis Cache Class to clear cache related to blogs.
 */
class BlogsRedisCache
{
    /**
     * Blogs Key Identifier
     */
    public const BLOG_SLUG_PREFIX = "blog:identifier";

    /**
     * Blogs Categories
     */
    public const BLOG_CATEGORIES = "blog:categories";

    /**
     * Blogs Listing
     */
    public const BLOG_LISTING = "blog:list";

    /**
     * TOP BRAND DEALS Identifier
     */
    public const TOP_BLOGS = 'top:blogs';

    /**
     * TOP Blogs by Category
     */
    public const TOP_BLOGS_BY_CATEGORY = 'top:blogs:caraousel';

    /**
     * NEW Blogs Identifier
     */
    public const NEW_BLOGS = 'new:blogs';

    /**
     * Blog Tags Identifier
     */
    public const BLOGS_TAGS = "blogs:tags";

    /**
     * Blog Tags by Category Identifier
     * blog:tags:{categoryId}
     */
    public const BLOG_TAGS_BY_CATEGORY = "blog:tags";

    /**
     * Tagged Blogs
     */
    public const TAGGED_BLOGS = "tagged:blogs";

    /**
     * Tagged Blogs by Category
     */
    public const TAGGED_BLOGS_BY_CATEGORY = "blog:tagKey:and:categoryId";

    /**
     * Related Articles
     */
    public const RELATED_ARTICLES = "related:articles";

    /**
     * Category Info Slug cache key
     */
    public const CATEGORY_INFO_SLUG = "category:info:slug";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * CMS Block Slug Key Identifier
     */
    public const CMS_BLOCK_SLUG_PREFIX = "cms:block:identifier";

    /**
     * @param RedisConnection $redisConnection
     */
    public function __construct(
        RedisConnection $redisConnection
    ) {
        $this->redisConnection = $redisConnection->connect();
    }

    /**
     * Update CMS Page Cache Data
     *
     * @param string $blogIdentifier
     * @return void
     */
    public function deleteBlogs(string $blogIdentifier): void
    {
        $this->deleteTopBlogs();
        $this->deleteNewBlogs();
        $this->deleteBlogCategories();
        $this->deleteBlogTags();
        $this->deleteBlogTagsByCategory();
        $this->deleteBlogByIdentifier($blogIdentifier);
        $this->deleteBlogListing();
        $this->deleteRelatedArticlesByBlogIdentifier($blogIdentifier);
        $this->deleteTaggedBlogs();
        $this->deleteTaggedBlogsByCategory();
        $this->deleteTopBlogsByCategory();
    }

    /**
     * Update CMS Block Cache Data
     *
     * @param string $blockIdentifier
     * @return void
     */
    public function deleteCmsBlock(string $blockIdentifier): void
    {
        if ($this->validateExistingKey(self::CMS_BLOCK_SLUG_PREFIX . ":" . $blockIdentifier)) {
            $this->redisConnection?->del(self::CMS_BLOCK_SLUG_PREFIX . ":" . $blockIdentifier);
        }
    }

    /**
     * Delete Tagged Blogs.
     *
     * @return void
     */
    public function deleteTaggedBlogs(): void
    {
        if ($this->validateExistingKey(self::TAGGED_BLOGS . "*")) {
            $this->redisConnection?->del(self::TAGGED_BLOGS . "*");
        }
    }

    /**
     * Delete Tagged Blogs By Category.
     *
     * @return void
     */
    public function deleteTaggedBlogsByCategory(): void
    {
        if ($this->validateExistingKey(self::TAGGED_BLOGS_BY_CATEGORY . "*")) {
            $this->redisConnection?->del(self::TAGGED_BLOGS_BY_CATEGORY . "*");
        }
    }

    /**
     * Delete Top Blogs By Category.
     *
     * @return void
     */
    public function deleteTopBlogsByCategory(): void
    {
        if ($this->validateExistingKey(self::TOP_BLOGS_BY_CATEGORY . "*")) {
            $this->redisConnection?->del(self::TOP_BLOGS_BY_CATEGORY . "*");
        }
    }

    /**
     * Delete Related Articles By Blog Identifier.
     *
     * @param string $blogIdentifier
     * @return void
     */
    public function deleteRelatedArticlesByBlogIdentifier(string $blogIdentifier): void
    {
        if ($this->validateExistingKey(self::RELATED_ARTICLES . ":" . $blogIdentifier)) {
            $this->redisConnection?->del(self::RELATED_ARTICLES . ":" . $blogIdentifier);
        }
    }

    /**
     * Delete All Related Articles By Blog Identifier.
     *
     * @return void
     */
    public function deleteAllRelatedArticlesByBlogIdentifier(): void
    {
        if ($this->validateExistingKey(self::RELATED_ARTICLES . "*")) {
            $this->redisConnection?->del(self::RELATED_ARTICLES . "*");
        }
    }

    /**
     * Delete Blog Listing.
     *
     * @return void
     */
    public function deleteBlogListing(): void
    {
        if ($this->validateExistingKey(self::BLOG_LISTING . "*")) {
            $this->redisConnection?->del(self::BLOG_LISTING . "*");
        }
    }

    /**
     * Delete Blog By Blog Identifier.
     *
     * @param string $blogIdentifier
     * @return void
     */
    public function deleteBlogByIdentifier(string $blogIdentifier): void
    {
        if ($this->validateExistingKey(self::BLOG_SLUG_PREFIX . ":" . $blogIdentifier)) {
            $this->redisConnection?->del(self::BLOG_SLUG_PREFIX . ":" . $blogIdentifier);
        }
    }

    /**
     * Delete All Blogs By Identifier.
     *
     * @return void
     */
    public function deleteAllBlogByIdentifier(): void
    {
        if ($this->validateExistingKey(self::BLOG_SLUG_PREFIX . "*")) {
            $this->redisConnection?->del(self::BLOG_SLUG_PREFIX . "*");
        }
    }

    /**
     * Get Already Existing Redis Keys.
     *
     * @param string $pattern
     * @return array
     */
    private function getKeys(string $pattern): array
    {
        return $this->redisConnection->keys($pattern);
    }

    /**
     * Delete Blog Categories.
     *
     * @return void
     */
    public function deleteBlogCategories(): void
    {
        if ($this->validateExistingKey(self::BLOG_CATEGORIES)) {
            $this->redisConnection?->del(self::BLOG_CATEGORIES);
        }
    }

    /**
     * Update All CMS Page Cache Data
     *
     * @return void
     */
    public function deleteAllBlogs(): void
    {
        $this->deleteTopBlogs();
        $this->deleteNewBlogs();
        $this->deleteBlogCategories();
        $this->deleteBlogTags();
        $this->deleteBlogTagsByCategory();
        $this->deleteBlogListing();
        $this->deleteTaggedBlogs();
        $this->deleteTaggedBlogsByCategory();
        $this->deleteTopBlogsByCategory();
        $this->deleteAllBlogByIdentifier();
        $this->deleteAllRelatedArticlesByBlogIdentifier();
    }

    /**
     * Update Blog Tags
     *
     * @return void
     */
    public function deleteBlogTags(): void
    {
        if ($this->validateExistingKey(self::BLOGS_TAGS)) {
            $this->redisConnection?->del(self::BLOGS_TAGS);
        }
    }

    /**
     * Validate Existing Cache Key.
     *
     * @param string $keyIdentifier
     * @return bool
     */
    public function validateExistingKey(string $keyIdentifier): bool
    {
        return $this->redisConnection && count($this->redisConnection->keys($keyIdentifier));
    }

    /**
     * Update Blog Tags
     *
     * @return void
     */
    public function deleteBlogTagsByCategory(): void
    {
        if ($this->validateExistingKey(self::BLOG_TAGS_BY_CATEGORY . "*")) {
            $this->redisConnection->del($this->getKeys(self::BLOG_TAGS_BY_CATEGORY . "*"));
        }
    }

    /**
     * Delete Top Blogs Cache
     *
     * @return void
     */
    public function deleteTopBlogs(): void
    {
        if ($this->validateExistingKey(self::TOP_BLOGS)) {
            $this->redisConnection?->del(self::TOP_BLOGS);
        }
    }

    /**
     * Delete New Blogs Cache
     *
     * @return void
     */
    public function deleteNewBlogs(): void
    {
        if ($this->validateExistingKey(self::NEW_BLOGS)) {
            $this->redisConnection?->del(self::NEW_BLOGS);
        }
    }

    /**
     * Delete Category Info Cache
     *
     * @param string $categorySlug
     * @return void
     */
    public function deleteCategoryInfo(string $categorySlug = ""): void
    {
        if ($this->validateExistingKey(self::CATEGORY_INFO_SLUG . ":" . $categorySlug . "*")) {
            $this->redisConnection?->del($this->getKeys(self::CATEGORY_INFO_SLUG . ":" . $categorySlug . "*"));
        }
    }
}
