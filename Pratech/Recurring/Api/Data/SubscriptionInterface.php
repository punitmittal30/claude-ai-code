<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pratech\Recurring\Api\Data;

interface SubscriptionInterface
{

    const MAX_REPEAT = 'max_repeat';
    const DURATION = 'duration';
    const PRODUCT_ID = 'product_id';
    const ORDER_ITEM_ID = 'order_item_id';
    const PRODUCT_NAME = 'product_name';
    const ORDER_ID = 'order_id';
    const CREATED_AT = 'created_at';
    const STATUS = 'status';
    const CANCELLATION_REASON = 'cancellation_reason';
    const PRODUCT_SKU = 'product_sku';
    const CUSTOMER_ID = 'customer_id';
    const PAYMENT_CODE = 'payment_code';
    const DURATION_TYPE = 'duration_type';
    const SUBSCRIPTION_ID = 'subscription_id';
    const LOCKED_PRICE = 'locked_price';
    const CUSTOMER_NAME = 'customer_name';
    const PRODUCT_QTY = 'product_qty';

    /**
     * Get subscription_id
     * @return string|null
     */
    public function getSubscriptionId();

    /**
     * Set subscription_id
     * @param string $subscriptionId
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setSubscriptionId($subscriptionId);

    /**
     * Get order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     * @param string $orderId
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setOrderId($orderId);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setProductId($productId);

    /**
     * Get product_name
     * @return string|null
     */
    public function getProductName();

    /**
     * Set product_name
     * @param string $productName
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setProductName($productName);

    /**
     * Get product_sku
     * @return string|null
     */
    public function getProductSku();

    /**
     * Set product_sku
     * @param string $productSku
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setProductSku($productSku);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get customer_name
     * @return string|null
     */
    public function getCustomerName();

    /**
     * Set customer_name
     * @param string $customerName
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setCustomerName($customerName);

    /**
     * Get duration
     * @return string|null
     */
    public function getDuration();

    /**
     * Set duration
     * @param string $duration
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setDuration($duration);

    /**
     * Get duration_type
     * @return string|null
     */
    public function getDurationType();

    /**
     * Set duration_type
     * @param string $durationType
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setDurationType($durationType);

    /**
     * Get locked_price
     * @return string|null
     */
    public function getLockedPrice();

    /**
     * Set locked_price
     * @param string $lockedPrice
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setLockedPrice($lockedPrice);

    /**
     * Get max_repeat
     * @return string|null
     */
    public function getMaxRepeat();

    /**
     * Set max_repeat
     * @param string $maxRepeat
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setMaxRepeat($maxRepeat);

    /**
     * Get payment_code
     * @return string|null
     */
    public function getPaymentCode();

    /**
     * Set payment_code
     * @param string $paymentCode
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setPaymentCode($paymentCode);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setStatus($status);

    /**
     * Get cancellation_reason
     * @return string|null
     */
    public function getCancellationReason();

    /**
     * Set cancellation_reason
     * @param string $cancellationReason
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setCancellationReason($cancellationReason);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get order_item_id
     * @return string|null
     */
    public function getOrderItemId();

    /**
     * Set order_item_id
     * @param string $orderItemId
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setOrderItemId($orderItemId);

    /**
     * Get product_qty
     * @return string|null
     */
    public function getProductQty();

    /**
     * Set product_qty
     * @param string $productQty
     * @return \Pratech\Recurring\Subscription\Api\Data\SubscriptionInterface
     */
    public function setProductQty($productQty);
}

