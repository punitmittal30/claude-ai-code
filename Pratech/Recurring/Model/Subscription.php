<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Recurring\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\Recurring\Api\Data\SubscriptionInterface;

class Subscription extends AbstractModel implements SubscriptionInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Pratech\Recurring\Model\ResourceModel\Subscription::class);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionId()
    {
        return $this->getData(self::SUBSCRIPTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSubscriptionId($subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getProductName()
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setProductName($productName)
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritDoc
     */
    public function getProductSku()
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * @inheritDoc
     */
    public function setProductSku($productSku)
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName()
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName($customerName)
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getDuration()
    {
        return $this->getData(self::DURATION);
    }

    /**
     * @inheritDoc
     */
    public function setDuration($duration)
    {
        return $this->setData(self::DURATION, $duration);
    }

    /**
     * @inheritDoc
     */
    public function getDurationType()
    {
        return $this->getData(self::DURATION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setDurationType($durationType)
    {
        return $this->setData(self::DURATION_TYPE, $durationType);
    }

    /**
     * @inheritDoc
     */
    public function getLockedPrice()
    {
        return $this->getData(self::LOCKED_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setLockedPrice($lockedPrice)
    {
        return $this->setData(self::LOCKED_PRICE, $lockedPrice);
    }

    /**
     * @inheritDoc
     */
    public function getMaxRepeat()
    {
        return $this->getData(self::MAX_REPEAT);
    }

    /**
     * @inheritDoc
     */
    public function setMaxRepeat($maxRepeat)
    {
        return $this->setData(self::MAX_REPEAT, $maxRepeat);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentCode()
    {
        return $this->getData(self::PAYMENT_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentCode($paymentCode)
    {
        return $this->setData(self::PAYMENT_CODE, $paymentCode);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getCancellationReason()
    {
        return $this->getData(self::CANCELLATION_REASON);
    }

    /**
     * @inheritDoc
     */
    public function setCancellationReason($cancellationReason)
    {
        return $this->setData(self::CANCELLATION_REASON, $cancellationReason);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getOrderItemId()
    {
        return $this->getData(self::ORDER_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderItemId($orderItemId)
    {
        return $this->setData(self::ORDER_ITEM_ID, $orderItemId);
    }

    /**
     * @inheritDoc
     */
    public function getProductQty()
    {
        return $this->getData(self::PRODUCT_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setProductQty($productQty)
    {
        return $this->setData(self::PRODUCT_QTY, $productQty);
    }

    /**
     * @inheritDoc
     */
    public function getValidTill()
    {
        return $this->getData(self::VALID_TILL);
    }

    /**
     * @inheritDoc
     */
    public function setValidTill($validTill)
    {
        return $this->setData(self::VALID_TILL, $validTill);
    }
}
