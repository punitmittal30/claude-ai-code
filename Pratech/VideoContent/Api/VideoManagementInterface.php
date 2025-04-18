<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Video Interface to expose video api.
 */
interface VideoManagementInterface
{
    /**
     * Get Video Data
     *
     * @param string $platform
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getVideos(string $platform, int $pincode): array;

    /**
     * Get Video Carousel Data
     *
     * @param string $page
     * @param string $platform
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getVideosCarousel(string $page, string $platform, int $pincode): array;
}
