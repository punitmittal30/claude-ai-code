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

namespace Pratech\ReviewRatings\Api\Data;

/**
 * Defines a data structure representing the media data by customer.
 */
interface MediaDataInterface
{
    /**
     * Get the media_type field.
     *
     * @return string The media_type field.
     */
    public function getMediaType(): string;

    /**
     * Set the media_type field.
     *
     * @param string $value The media_type field.
     * @return null
     */
    public function setMediaType(string $value);

    /**
     * Get the url field.
     *
     * @return string The url field.
     */
    public function getUrl(): string;

    /**
     * Set the url field.
     *
     * @param string $value The url field.
     * @return null
     */
    public function setUrl(string $value);
}
