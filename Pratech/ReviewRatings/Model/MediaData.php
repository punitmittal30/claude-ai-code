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

namespace Pratech\ReviewRatings\Model;

use Pratech\ReviewRatings\Api\Data\MediaDataInterface;

/**
 * Rating Data Class to store rating info
 */
class MediaData implements MediaDataInterface
{
    /**
     * @var string
     */
    private $mediaType;

    /**
     * @var string
     */
    private $url;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->mediaType = '';
        $this->url = '';
    }

    /**
     * Get the media_type field.
     *
     * @return string The media_type field.
     * @api
     */
    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    /**
     * Set the media_type field.
     *
     * @param string $value The media_type field.
     * @return null
     * @api
     */
    public function setMediaType(string $value)
    {
        $this->mediaType = $value;
    }

    /**
     * Get the url field.
     *
     * @return string The url field.
     * @api
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the url field.
     *
     * @param string $value The url field.
     * @return null
     * @api
     */
    public function setUrl(string $value)
    {
        $this->url = $value;
    }
}
