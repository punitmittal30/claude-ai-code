<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Api\Data;

/**
 * Defines a data structure representing a rating vote data by customer.
 */
interface ProductReviewInterface
{
    /**
     * Get the product sku field.
     *
     * @return string The sku field.
     */
    public function getSku(): string;

    /**
     * Set the product sku field.
     *
     * @param string $value The new sku field.
     * @return null
     */
    public function setSku(string $value);

    /**
     * Get the rating data field.
     *
     * @return \Pratech\ReviewRatings\Api\Data\RatingInterface[] The rating data field.
     */
    public function getRatingData(): array;

    /**
     * Set the rating data field.
     *
     * @param \Pratech\ReviewRatings\Api\Data\RatingInterface[] $value The rating data field.
     * @return null
     */
    public function setRatingData(array $value);

    /**
     * Get the review keywords field.
     *
     * @return string The keywords field.
     */
    public function getKeywords(): string;

    /**
     * Set the review keywords field.
     *
     * @param string $value The new keywords field.
     * @return null
     */
    public function setKeywords(string $value);

    /**
     * Get the review title field.
     *
     * @return string The title field.
     */
    public function getTitle(): string;

    /**
     * Set the review title field.
     *
     * @param string $value The new title field.
     * @return null
     */
    public function setTitle(string $value);

    /**
     * Get the review detail field.
     *
     * @return string The detail field.
     */
    public function getDetail(): string;

    /**
     * Set the review detail field.
     *
     * @param string $value The new detail field.
     * @return null
     */
    public function setDetail(string $value);

    /**
     * Get the media data field.
     *
     * @return \Pratech\ReviewRatings\Api\Data\MediaDataInterface[] The media data field.
     */
    public function getMediaData(): array;

    /**
     * Set the media data field.
     *
     * @param \Pratech\ReviewRatings\Api\Data\MediaDataInterface[] $value The media data field.
     * @return null
     */
    public function setMediaData(array $value);
}
