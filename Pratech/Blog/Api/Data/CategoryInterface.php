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

namespace Pratech\Blog\Api\Data;

interface CategoryInterface
{
    public const CATEGORY_ID = 'category_id';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const NAME = 'name';
    public const URL_KEY = 'url_key';
    public const DESCRIPTION = 'description';
    public const TITLE = 'title';
    public const CREATED_AT = 'created_at';
    public const STATUS = 'status';
    public const SORT_ORDER = 'sort_order';
    public const META_TAGS = 'meta_tags';
    public const THUMBNAIL_IMAGE = 'thumbnail_image';
    public const BANNER_IMAGE = 'banner_image';

    /**
     * Get category_id
     *
     * @return string|null
     */
    public function getCategoryId();

    /**
     * Set category_id
     *
     * @param string $categoryId
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setCategoryId($categoryId);

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setName($name);

    /**
     * Get url_key
     *
     * @return string|null
     */
    public function getUrlKey();

    /**
     * Set url_key
     *
     * @param string $urlKey
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setUrlKey($urlKey);

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     *
     * @param string $title
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setTitle($title);

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param string $description
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setDescription($description);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setStatus($status);

    /**
     * Get sort_order
     *
     * @return string|null
     */
    public function getSortOrder();

    /**
     * Set sort_order
     *
     * @param string $sortOrder
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * Get meta_title
     *
     * @return string|null
     */
    public function getMetaTitle();

    /**
     * Set meta_title
     *
     * @param string $metaTitle
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setMetaTitle($metaTitle);

    /**
     * Get meta_tags
     *
     * @return string|null
     */
    public function getMetaTags();

    /**
     * Set meta_tags
     *
     * @param string $metaTags
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setMetaTags($metaTags);

    /**
     * Get meta_description
     *
     * @return string|null
     */
    public function getMetaDescription();

    /**
     * Set meta_description
     *
     * @param string $metaDescription
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setMetaDescription($metaDescription);

    /**
     * Get thumbnail_image
     *
     * @return string|null
     */
    public function getThumbnailImage();

    /**
     * Set thumbnail_image
     *
     * @param string $thumbnailImage
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setThumbnailImage($thumbnailImage);

    /**
     * Get banner_image
     *
     * @return string|null
     */
    public function getBannerImage();

    /**
     * Set banner_image
     *
     * @param string $bannerImage
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setBannerImage($bannerImage);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Pratech\Blog\Category\Api\Data\CategoryInterface
     */
    public function setCreatedAt($createdAt);
}
