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

use Magento\Framework\Api\SearchCriteriaInterface;

interface MediaRepositoryInterface
{

    /**
     * Save Media
     *
     * @param \Pratech\ReviewRatings\Api\Data\MediaInterface $media
     * @return \Pratech\ReviewRatings\Api\Data\MediaInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Pratech\ReviewRatings\Api\Data\MediaInterface $media
    );

    /**
     * Retrieve Media
     *
     * @param string $mediaId
     * @return \Pratech\ReviewRatings\Api\Data\MediaInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($mediaId);

    /**
     * Retrieve Media matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\ReviewRatings\Api\Data\MediaSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Media
     *
     * @param \Pratech\ReviewRatings\Api\Data\MediaInterface $media
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Pratech\ReviewRatings\Api\Data\MediaInterface $media
    );

    /**
     * Delete Media by ID
     *
     * @param string $mediaId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($mediaId);
}
