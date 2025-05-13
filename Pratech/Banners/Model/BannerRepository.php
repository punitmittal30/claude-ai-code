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

namespace Pratech\Banners\Model;

use Pratech\Banners\Api\BannerRepositoryInterface;
use Pratech\Banners\Helper\Data;
use Pratech\Base\Model\Data\Response;

/**
 * BannerRepository API Class
 */
class BannerRepository implements BannerRepositoryInterface
{
    /**
     * Constant for CATEGORY API RESOURCE
     */
    public const BANNER_API_RESOURCE = 'banner';

    /**
     * Banner Repository Constructor
     *
     * @param Data $bannerHelper
     * @param Response $response
     */
    public function __construct(
        private Data     $bannerHelper,
        private Response $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBannersByCategoryId(int $categoryId, string $type = "", int $pincode = null): array
    {
        $banners = $this->bannerHelper->getBannersByCategoryId($categoryId, $type, $pincode);
        return $this->response->getResponse(
            200,
            'success',
            self::BANNER_API_RESOURCE,
            $banners
        );
    }

    /**
     * @inheritDoc
     */
    public function getBannersByCategorySlug(string $slug, string $type = "", int $pincode = null): array
    {
        $banners = $this->bannerHelper->getBannersByCategorySlug($slug, $type, $pincode);
        return $this->response->getResponse(
            200,
            'success',
            self::BANNER_API_RESOURCE,
            $banners
        );
    }
}
