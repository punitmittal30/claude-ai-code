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

interface SubscriptionRepositoryInterface
{

    /**
     * Save Subscription
     *
     * @param \Pratech\Recurring\Api\Data\SubscriptionInterface $subscription
     * @return \Pratech\Recurring\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Pratech\Recurring\Api\Data\SubscriptionInterface $subscription
    );

    /**
     * Retrieve Subscription
     *
     * @param string $subscriptionId
     * @return \Pratech\Recurring\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($subscriptionId);

    /**
     * Retrieve Subscription matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Recurring\Api\Data\SubscriptionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Subscription
     *
     * @param \Pratech\Recurring\Api\Data\SubscriptionInterface $subscription
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Pratech\Recurring\Api\Data\SubscriptionInterface $subscription
    );

    /**
     * Delete Subscription by ID
     *
     * @param string $subscriptionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($subscriptionId);
}
