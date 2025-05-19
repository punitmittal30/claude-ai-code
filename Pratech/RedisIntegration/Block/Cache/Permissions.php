<?php

namespace Pratech\RedisIntegration\Block\Cache;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Permissions implements ArgumentInterface
{
    /**
     * Permissions constructor.
     *
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        private AuthorizationInterface $authorization
    ) {
    }

    /**
     * Has Access To Flush Attribute Mapping Feature
     *
     * @return bool
     */
    public function hasAccessToFlushAttributeMapping(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_attribute_mapping');
    }

    /**
     * Has Access To Catalog Cache Post Import Feature
     *
     * @return bool
     */
    public function hasAccessToCatalogCachePostImport(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_post_import');
    }

    /**
     * Has Access To Flush Reviews Feature
     *
     * @return bool
     */
    public function hasAccessToFlushReviews(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_reviews');
    }

    /**
     * Has Access To Flush Catalog Feature
     *
     * @return bool
     */
    public function hasAccessToFlushCatalog(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_catalog');
    }

    /**
     * Has Access To Redis Actions Feature
     *
     * @return bool
     */
    public function hasAccessToRedisActions(): bool
    {
        return ($this->hasAccessToFlushAttributeMapping()
                || $this->hasAccessToFlushReviews());
    }

    /**
     * Has Access To Flush Catalog Feature
     *
     * @return bool
     */
    public function hasAccessToFlushSystemConfig(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::flush_system_config');
    }

    /**
     * Has Access To Flush Customer Widget Cache
     *
     * @return bool
     */
    public function hasAccessToFlushCustomerWidget(): bool
    {
        return $this->authorization->isAllowed('Magento_Backend::customer_widget');
    }

    /**
     * Has Access To Flush Quiz Cache
     *
     * @return bool
     */
    public function hasAccessToFlushQuizCache(): bool
    {
        return $this->authorization->isAllowed('Pratech_Quiz::manage_quiz');
    }

    /**
     * Has Access To Flush Video Cache
     *
     * @return bool
     */
    public function hasAccessToFlushVideoCache(): bool
    {
        return $this->authorization->isAllowed('Pratech_VideoContent::manage_videos');
    }
}
