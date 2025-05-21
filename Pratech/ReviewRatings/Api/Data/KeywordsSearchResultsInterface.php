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

interface KeywordsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Keywords list
     *
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface[]
     */
    public function getItems();

    /**
     * Set mapping_value list
     *
     * @param \Pratech\ReviewRatings\Api\Data\KeywordsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
