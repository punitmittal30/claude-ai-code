<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Api\Data;

/**
 * Interface CancelOrderRequestItemInterface
 *
 * @api
 */
interface CancelOrderRequestItemInterface
{
    public const ORDER_ITEM_ID = 'order_item_id';

    public const CANCEL_QTY = 'cancel_qty';

    public const REASON = 'reason';

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
     * Get Cancel Qty
     *
     * @return int
     */
    public function getCancelQty();

    /**
     * Set Cancel Qty
     *
     * @param int $cancelQty
     * @return $this
     */
    public function setCancelQty(int $cancelQty);

    /**
     * Get Reason
     *
     * @return string|null
     */
    public function getReason();

    /**
     * Set Reason
     *
     * @param string|null $reason
     * @return $this
     */
    public function setReason(?string $reason);
}
