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

namespace Pratech\Order\Model\Data;

use Pratech\Order\Api\Data\ProductReviewInterface;

/**
 * Review Data Class to store rating info
 */
class ProductReview implements ProductReviewInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var \Pratech\ReviewRatings\Api\Data\RatingInterface[]
     */
    private $ratingData;

    /**
     * @var string
     */
    private $keywords;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $detail;

    /**
     * @var \Pratech\ReviewRatings\Api\Data\MediaDataInterface[]
     */
    private $mediaData;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->sku = '';
        $this->ratingData = [];
        $this->keywords = '';
        $this->title = '';
        $this->detail = '';
        $this->mediaData = [];
    }

    /**
     * Get the product sku field.
     *
     * @return string The sku field.
     * @api
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Set the product sku field.
     *
     * @param string $value The new sku field.
     * @return null
     * @api
     */
    public function setSku(string $value)
    {
        $this->sku = $value;
    }

    /**
     * Get the rating data field.
     *
     * @return \Pratech\ReviewRatings\Api\Data\RatingInterface[] The rating data field.
     * @api
     */
    public function getRatingData(): array
    {
        return $this->ratingData;
    }

    /**
     * Set the rating data field.
     *
     * @param \Pratech\ReviewRatings\Api\Data\RatingInterface[] $value The rating data field.
     * @return null
     * @api
     */
    public function setRatingData(array $value)
    {
        $this->ratingData = $value;
    }

    /**
     * Get the review keywords field.
     *
     * @return string The keywords field.
     * @api
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * Set the review keywords field
     *
     * @param string $value The new keywords field.
     * @return null
     * @api
     */
    public function setKeywords(string $value)
    {
        $this->keywords = $value;
    }

    /**
     * Get the review title field.
     *
     * @return string The title field.
     * @api
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the review title field
     *
     * @param string $value The new title field.
     * @return null
     * @api
     */
    public function setTitle(string $value)
    {
        $this->title = $value;
    }

    /**
     * Get the review detail field.
     *
     * @return string The detail field.
     * @api
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * Set the review detail field
     *
     * @param string $value The new detail field.
     * @return null
     * @api
     */
    public function setDetail(string $value)
    {
        $this->detail = $value;
    }

    /**
     * Get the media data field.
     *
     * @return \Pratech\ReviewRatings\Api\Data\MediaDataInterface[] The media data field.
     * @api
     */
    public function getMediaData(): array
    {
        return $this->mediaData;
    }

    /**
     * Set the media data field.
     *
     * @param \Pratech\ReviewRatings\Api\Data\MediaDataInterface[] $value The media data field.
     * @return null
     * @api
     */
    public function setMediaData(array $value)
    {
        $this->mediaData = $value;
    }
}
