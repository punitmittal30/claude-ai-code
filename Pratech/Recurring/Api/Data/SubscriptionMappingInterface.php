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

namespace Pratech\Recurring\Api\Data;

interface SubscriptionMappingInterface
{

    public const SUBSCRIPTIONMAPPING_ID = 'subscription_mapping_id';
    public const SUBSCRIPTION_ID = 'subscription_id';
    public const ORDER_ID = 'order_id';
    public const CREATED_AT = 'created_at';

    /**
     * Get subscription_mapping_id
     *
     * @return string|null
     */
    public function getSubscriptionMappingId();

    /**
     * Set subscription_mapping_id
     *
     * @param string $subscriptionMappingId
     * @return \Pratech\Recurring\SubscriptionMapping\Api\Data\SubscriptionMappingInterface
     */
    public function setSubscriptionMappingId($subscriptionMappingId);

    /**
     * Get subscription_id
     *
     * @return string|null
     */
    public function getSubscriptionId();

    /**
     * Set subscription_id
     *
     * @param string $subscriptionId
     * @return \Pratech\Recurring\SubscriptionMapping\Api\Data\SubscriptionMappingInterface
     */
    public function setSubscriptionId($subscriptionId);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \Pratech\Recurring\SubscriptionMapping\Api\Data\SubscriptionMappingInterface
     */
    public function setOrderId($orderId);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Pratech\Recurring\SubscriptionMapping\Api\Data\SubscriptionMappingInterface
     */
    public function setCreatedAt($createdAt);
}
