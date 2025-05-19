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

interface TagInterface
{
    public const TAG_ID = 'tag_id';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const NAME = 'name';
    public const URL_KEY = 'url_key';
    public const META_TAGS = 'meta_tags';

    /**
     * Get tag_id
     *
     * @return string|null
     */
    public function getTagId();

    /**
     * Set tag_id
     *
     * @param string $tagId
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
     */
    public function setTagId($tagId);

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
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
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
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
     */
    public function setUrlKey($urlKey);

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
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
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
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
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
     * @return \Pratech\Blog\Tag\Api\Data\TagInterface
     */
    public function setMetaDescription($metaDescription);
}
