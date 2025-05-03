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

namespace Hyuga\CacheManagement\Api;

interface NodeRedisServiceInterface
{
    /**
     * Key Identifiers
     * @todo
     */

    public const NODE_CACHING_LIST = [
        'dark_store_url_list' => 'warehouse:url:list',
        'pincode_serviceability' => 'pincode:serviceability',
        'category_id_slug_mapping' => 'catalog:categories:mapping',
        'categories_by_pincode' => 'yet:to_decide:pincode'
    ];

    /**
     * Clean Pincode Caches And Dark Store Slugs.
     *
     * @return void
     */
    public function cleanAllPincodeCachesAndDarkStoreSlugs(): void;

    /**
     * Clean Category Id Slug Mapping.
     *
     * @return void
     */
    public function cleanCategoryIdSlugMapping(): void;
}
