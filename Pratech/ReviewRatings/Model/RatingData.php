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

use Pratech\ReviewRatings\Api\Data\RatingInterface;

/**
 * Rating Data Class to store rating info
 */
class RatingData implements RatingInterface
{
    /**
     * @var string
     */
    private $ratingId;

    /**
     * @var string
     */
    private $ratingCode;

    /**
     * @var string
     */
    private $ratingValue;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ratingCode = '';
        $this->ratingId = '';
        $this->ratingValue = '';
    }

    /**
     * Get the rating_id field.
     *
     * @return int The name field.
     * @api
     */
    public function getRatingId(): int
    {
        return $this->ratingId;
    }

    /**
     * Set the rating_id field.
     *
     * @param int $value The new name field.
     * @return null
     * @api
     */
    public function setRatingId(int $value)
    {
        $this->ratingId = $value;
    }

    /**
     * Get the rating code field.
     *
     * @return string The province field.
     * @api
     */
    public function getRatingCode(): string
    {
        return $this->ratingCode;
    }

    /**
     * Set the rating code field.
     *
     * @param string $value The new province field.
     * @return null
     * @api
     */
    public function setRatingCode(string $value)
    {
        $this->ratingCode = $value;
    }

    /**
     * Get the rating value field.
     *
     * @return int The name field.
     * @api
     */
    public function getRatingValue(): int
    {
        return $this->ratingValue;
    }

    /**
     * Set the rating value field.
     *
     * @param int $value The new name field.
     * @return null
     * @api
     */
    public function setRatingValue(int $value)
    {
        $this->ratingValue = $value;
    }
}
