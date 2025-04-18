<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\ReviewRatings\Api\ReviewInterface;
use Pratech\ReviewRatings\Helper\Data;

/**
 * Review Management Class to expose apis
 */
class ReviewManagement implements ReviewInterface
{
    /**
     * CATALOG API RESOURCE CONSTANT
     */
    public const CATALOG_API_RESOURCE = 'catalog';

    /**
     * @param Data $reviewHelper
     * @param Response $response
     */
    public function __construct(
        private Data     $reviewHelper,
        private Response $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProductReviewFormData(string $productSlug, int $customerId): array
    {
        return $this->response->getResponse(
            200,
            "success",
            self::CATALOG_API_RESOURCE,
            $this->reviewHelper->getProductReviewFormData($productSlug, $customerId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getReviewsByProductId(int $productId): array
    {
        return $this->response->getResponse(
            200,
            "success",
            self::CATALOG_API_RESOURCE,
            $this->reviewHelper->getReviewsByProductId($productId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getReviewsByProductSlug(string $productSlug): array
    {
        return $this->response->getResponse(
            200,
            "success",
            self::CATALOG_API_RESOURCE,
            $this->reviewHelper->getReviewsByProductSlug($productSlug)
        );
    }

    /**
     * @inheritDoc
     */
    public function getRatings(int $storeId = null): array
    {
        return $this->response->getResponse(
            200,
            "success",
            self::CATALOG_API_RESOURCE,
            $this->reviewHelper->getRatings($storeId)
        );
    }

    /**
     * @inheritDoc
     */
    public function writeReviews(
        int    $productId,
        string $nickname,
        string $title,
        string $detail,
        array  $ratingData,
        ?int   $customerId = null,
        string $keywords = '',
        array  $mediaData = []
    ): array {
        $response = $this->reviewHelper
            ->writeReviews($productId, $nickname, $title, $detail, $ratingData, $customerId, $keywords, $mediaData);
        return $this->response->getResponse(
            200,
            $response["message"],
            self::CATALOG_API_RESOURCE,
            $response
        );
    }
}
