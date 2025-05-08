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

namespace Pratech\ReviewRatings\Api;

/**
 * Interface ReviewInterface
 */
interface ReviewInterface
{
    /**
     * Get Product Review Form Data
     *
     * @param string $productSlug
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductReviewFormData(string $productSlug, int $customerId): array;

    /**
     * Return Added review item.
     *
     * @param int $productId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReviewsByProductId(int $productId): array;

    /**
     * Return Added review item.
     *
     * @param string $productSlug
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReviewsByProductSlug(string $productSlug): array;

    /**
     * Return Rating options.
     *
     * @param int|null $storeId
     * @return array
     */
    public function getRatings(int $storeId = null): array;

    /**
     * Write Reviews
     *
     * @param int $productId
     * @param string $nickname
     * @param string $title
     * @param string $detail
     * @param \Pratech\ReviewRatings\Api\Data\RatingInterface[] $ratingData
     * @param int|null $customerId
     * @param string $keywords
     * @param \Pratech\ReviewRatings\Api\Data\MediaDataInterface[] $mediaData
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
    ): array;
}
