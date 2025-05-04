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

use Magento\Framework\Model\AbstractModel;
use Pratech\Blog\Api\Data\TagInterface;

class Tag extends AbstractModel implements TagInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'blog_tag';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Pratech\Blog\Model\ResourceModel\Tag::class);
    }

    /**
     * @inheritDoc
     */
    public function getTagId()
    {
        return $this->getData(self::TAG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTagId($tagId)
    {
        return $this->setData(self::TAG_ID, $tagId);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getUrlKey()
    {
        return $this->getData(self::URL_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setUrlKey($urlKey)
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    /**
     * @inheritDoc
     */
    public function getMetaTitle()
    {
        return $this->getData(self::META_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setMetaTitle($metaTitle)
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * @inheritDoc
     */
    public function getMetaTags()
    {
        return $this->getData(self::META_TAGS);
    }

    /**
     * @inheritDoc
     */
    public function setMetaTags($metaTags)
    {
        return $this->setData(self::META_TAGS, $metaTags);
    }

    /**
     * @inheritDoc
     */
    public function getMetaDescription()
    {
        return $this->getData(self::META_DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setMetaDescription($metaDescription)
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
    }
}
