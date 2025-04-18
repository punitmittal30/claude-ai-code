<?php

namespace Hyuga\CacheManagement\Api;

interface NodeRedisServiceInterface
{
    /**
     * Key Identifiers
     */
    public const DARK_STORE_URL_LIST = "warehouse:url:list";
    public const PINCODE_SERVICEABILITY = "pincode:serviceability";
    public const CATEGORY_ID_SLUG_MAPPING = 'catalog:categories:mapping';

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
