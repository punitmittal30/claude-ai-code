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

namespace Pratech\Recurring\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Subscription Management Interface for api.
 */
interface SubscriptionManagementInterface
{
    /**
     * Get Subscription Form Data
     *
     * @param int $orderId
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getSubscriptionFormData(int $orderId): array;

    /**
     * Create Subscription
     *
     * @param int $orderId
     * @param \Pratech\Recurring\Api\Data\SubscriptionRequestItemInterface[] $items
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function createSubscription(int $orderId, array $items): array;
}
