<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ReviewRatings\Api\Data;

interface MediaInterface
{

    public const STATUS = 'status';
    public const REVIEW_ID = 'review_id';
    public const URL = 'url';
    public const MEDIA_ID = 'media_id';

    /**
     * Get media_id
     *
     * @return string|null
     */
    public function getMediaId();

    /**
     * Set media_id
     *
     * @param string $mediaId
     * @return MediaInterface
     */
    public function setMediaId($mediaId);

    /**
     * Get review_id
     *
     * @return string|null
     */
    public function getReviewId();

    /**
     * Set review_id
     *
     * @param string $reviewId
     * @return MediaInterface
     */
    public function setReviewId($reviewId);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return MediaInterface
     */
    public function setStatus($status);

    /**
     * Get url
     *
     * @return string|null
     */
    public function getUrl();

    /**
     * Set url
     *
     * @param string $url
     * @return MediaInterface
     */
    public function setUrl($url);
}
