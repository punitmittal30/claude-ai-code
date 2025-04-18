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

namespace Pratech\ReviewRatings\Api;

interface KeywordsRepositoryInterface
{

    /**
     * Save Keywords
     *
     * @param \Pratech\ReviewRatings\Api\Data\KeywordsInterface $keywords
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Pratech\ReviewRatings\Api\Data\KeywordsInterface $keywords
    );

    /**
     * Retrieve Keywords
     *
     * @param string $keywordsId
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($keywordsId);

    /**
     * Retrieve Keywords matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\ReviewRatings\Api\Data\KeywordsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Keywords
     *
     * @param \Pratech\ReviewRatings\Api\Data\KeywordsInterface $keywords
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Pratech\ReviewRatings\Api\Data\KeywordsInterface $keywords
    );

    /**
     * Delete Keywords by ID
     *
     * @param string $keywordsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($keywordsId);
}
