<?php

namespace Hyuga\Catalog\Api;

/**
 * Category Repository Interface to expose categories api.
 */
interface CategoryRepositoryInterface
{
    /**
     * Get Category Id Slug Mapping
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryIdSlugMapping(): array;
}
