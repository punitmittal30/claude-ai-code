<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Recurring\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SubscriptionMappingRepositoryInterface
{

    /**
     * Save SubscriptionMapping
     *
     * @param \Pratech\Recurring\Api\Data\SubscriptionMappingInterface $subscriptionMapping
     * @return \Pratech\Recurring\Api\Data\SubscriptionMappingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Pratech\Recurring\Api\Data\SubscriptionMappingInterface $subscriptionMapping
    );

    /**
     * Retrieve SubscriptionMapping
     *
     * @param string $subscriptionMappingId
     * @return \Pratech\Recurring\Api\Data\SubscriptionMappingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($subscriptionMappingId);

    /**
     * Retrieve SubscriptionMapping matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Recurring\Api\Data\SubscriptionMappingSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SubscriptionMapping
     *
     * @param \Pratech\Recurring\Api\Data\SubscriptionMappingInterface $subscriptionMapping
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Pratech\Recurring\Api\Data\SubscriptionMappingInterface $subscriptionMapping
    );

    /**
     * Delete SubscriptionMapping by ID
     *
     * @param string $subscriptionMappingId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($subscriptionMappingId);
}
