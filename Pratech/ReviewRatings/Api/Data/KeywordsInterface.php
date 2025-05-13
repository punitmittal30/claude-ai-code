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

interface KeywordsInterface
{

    public const ENTITY_ID = 'entity_id';
    public const MAPPING_VALUE = 'mapping_value';
    public const RATING_ONE = 'rating_one';
    public const RATING_TWO = 'rating_two';
    public const RATING_THREE = 'rating_three';
    public const RATING_FOUR = 'rating_four';
    public const RATING_FIVE = 'rating_five';

    /**
     * Get entity_id
     *
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param string $keywordsId
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setEntityId($keywordsId);

    /**
     * Get mapping_value
     *
     * @return string|null
     */
    public function getMappingValue();

    /**
     * Set mapping_value
     *
     * @param string $mappingValue
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setMappingValue($mappingValue);

    /**
     * Get rating_one
     *
     * @return string|null
     */
    public function getRatingOne();

    /**
     * Set rating_one
     *
     * @param string $ratingOne
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setRatingOne($ratingOne);

    /**
     * Get rating_two
     *
     * @return string|null
     */
    public function getRatingTwo();

    /**
     * Set rating_two
     *
     * @param string $ratingTwo
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setRatingTwo($ratingTwo);

    /**
     * Get rating_three
     *
     * @return string|null
     */
    public function getRatingThree();

    /**
     * Set rating_three
     *
     * @param string $ratingThree
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setRatingThree($ratingThree);

    /**
     * Get rating_four
     *
     * @return string|null
     */
    public function getRatingFour();

    /**
     * Set rating_four
     *
     * @param string $ratingFour
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setRatingFour($ratingFour);

    /**
     * Get rating_five
     *
     * @return string|null
     */
    public function getRatingFive();

    /**
     * Set rating_five
     *
     * @param string $ratingFive
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     */
    public function setRatingFive($ratingFive);
}
