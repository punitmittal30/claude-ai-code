<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\RedisIntegration\Block\Cache;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\AuthorizationInterface;

class Additional extends \Magento\Backend\Block\Template
{
    /**
     * @param Context                $context
     * @param AuthorizationInterface $authorization
     * @param array                  $data
     */
    public function __construct(
        Context $context,
        protected AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Post Import Cache Controller Url
     *
     * @return string
     */
    public function getPostImportCacheUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanPostImportCache');
    }

    /**
     * Get Attribute Mapping Cache Clean Url
     *
     * @return string
     */
    public function getCleanAttributeMappingCacheUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanAttributeMappingCache');
    }

    /**
     * Get Reviews Clean Url
     *
     * @return string
     */
    public function getCleanReviewsUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanReviews');
    }

    /**
     * Get Catalog Clean Url
     *
     * @return string
     */
    public function getCleanCatalogUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanCatalog');
    }

    /**
     * Get System Config Url
     *
     * @return string
     */
    public function getSystemConfigUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanSystemConfig');
    }

    /**
     * Get Customer Widget Url
     *
     * @return string
     */
    public function getCustomerWidgetUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanCustomerWidget');
    }

    /**
     * Get Quiz Url
     *
     * @return string
     */
    public function getQuizUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanQuizCache');
    }

    /**
     * Get Video Url
     *
     * @return string
     */
    public function getVideoUrl(): string
    {
        return $this->getUrl('pratech_redis/cache/cleanVideoCache');
    }
}
