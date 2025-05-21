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
namespace Pratech\Recurring\Api\Data;

/**
 * Interface SubscriptionRequestItemInterface
 *
 * @api
 */
interface SubscriptionRequestItemInterface
{
    public const ORDER_ITEM_ID = 'order_item_id';

    public const PRODUCT_QTY = 'product_qty';

    public const DURATION = 'duration';

    public const DURATION_TYPE = 'duration_type';

    public const MAX_REPEAT = 'max_repeat';

    public const PAYMENT_CODE = 'payment_code';

    /**
     * Get Magento Order Item ID
     *
     * @return int
     */
    public function getOrderItemId();

    /**
     * Set Magento Order Item ID
     *
     * @param int $orderItemId
     * @return $this
     */
    public function setOrderItemId(int $orderItemId);

    /**
     * Get Product Qty
     *
     * @return int
     */
    public function getProductQty();

    /**
     * Set product Qty
     *
     * @param int|null $productQty
     * @return $this
     */
    public function setProductQty(?int $productQty);

    /**
     * Get Duration
     *
     * @return int|null
     */
    public function getDuration();

    /**
     * Set Duration
     *
     * @param int|null $duration
     * @return $this
     */
    public function setDuration(?int $duration);

    /**
     * Get Duration Type
     *
     * @return string|null
     */
    public function getDurationType();

    /**
     * Set Duration Type
     *
     * @param string|null $durationType
     * @return $this
     */
    public function setDurationType(?string $durationType);

    /**
     * Get Max Repeat
     *
     * @return int|null
     */
    public function getMaxRepeat();

    /**
     * Set Max Repeat
     *
     * @param int|null $maxRepeat
     * @return $this
     */
    public function setMaxRepeat(?int $maxRepeat);

    /**
     * Get Payment Code
     *
     * @return string
     */
    public function getPaymentCode();

    /**
     * Set Payment Code
     *
     * @param string $paymentCode
     * @return $this
     */
    public function setPaymentCode(string $paymentCode);
}
