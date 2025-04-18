<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Block\Adminhtml\Cache;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class AdditionalButtons extends Template
{
    /**
     * Get URL for flushing pincode cache
     *
     * @return string
     */
    public function getFlushPincodeCacheUrl(): string
    {
        return $this->getUrl('hyuga_cachemanagement/cache/flushPincodeCache');
    }

    /**
     * Get URL for flushing warehouse products cache
     *
     * @return string
     */
    public function getFlushWarehouseProductsCacheUrl(): string
    {
        return $this->getUrl('hyuga_cachemanagement/cache/flushWarehouseProductsCache');
    }

    /**
     * Get URL for flushing dark store cache
     *
     * @return string
     */
    public function getFlushNearestDarkStoreCacheUrl(): string
    {
        return $this->getUrl('hyuga_cachemanagement/cache/flushNearestDarkStoreCache');
    }

    /**
     * Get URL for flushing warehouse filters cache
     *
     * @return string
     */
    public function getFlushWarehouseFiltersCacheUrl(): string
    {
        return $this->getUrl('hyuga_cachemanagement/cache/flushWarehouseFiltersCache');
    }

    /**
     * Get URL for flushing dark store cache
     *
     * @return string
     */
    public function getFlushAvailableDarkStoreCacheUrl(): string
    {
        return $this->getUrl('hyuga_cachemanagement/cache/flushAvailableDarkStoreCache');
    }
}
