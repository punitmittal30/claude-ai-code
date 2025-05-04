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

namespace Pratech\ReviewRatings\Api\Data;

/**
 * Defines a data structure representing a rating vote data by customer.
 */
interface RatingInterface
{
    /**
     * Get the rating_id field.
     *
     * @return int The name field.
     */
    public function getRatingId(): int;

    /**
     * Set the rating_id field.
     *
     * @param int $value The new name field.
     * @return null
     */
    public function setRatingId(int $value);

    /**
     * Get the rating code field.
     *
     * @return string The province field.
     */
    public function getRatingCode(): string;

    /**
     * Set the rating code field.
     *
     * @param string $value The new province field.
     * @return null
     */
    public function setRatingCode(string $value);

    /**
     * Get the rating value field.
     *
     * @return int The name field.
     */
    public function getRatingValue(): int;

    /**
     * Set the rating value field.
     *
     * @param int $value The new name field.
     * @return null
     */
    public function setRatingValue(int $value);
}
