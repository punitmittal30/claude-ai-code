<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface BannerRepositoryInterface
{
    /**
     * Get Banners By Category ID
     *
     * @param int $categoryId
     * @param string $type
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getBannersByCategoryId(
        int $categoryId,
        string $type = "",
        int $pincode = null
    ): array;

    /**
     * Get Banners By Category Slug
     *
     * @param string $slug
     * @param string $type
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getBannersByCategorySlug(
        string $slug,
        string $type = "",
        int $pincode = null
    ): array;
}
