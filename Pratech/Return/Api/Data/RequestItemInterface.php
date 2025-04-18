<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Api\Data;

/**
 * Interface RequestItemInterface
 */
interface RequestItemInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const REQUEST_ITEM_ID = 'request_item_id';
    public const REQUEST_ID = 'request_id';
    public const ORDER_ITEM_ID = 'order_item_id';
    public const QTY = 'qty';
    public const REQUEST_QTY = 'request_qty';
    public const REFUNDED_AMOUNT = 'refunded_amount';
    public const REASON_ID = 'reason_id';
    public const ITEM_STATUS = 'item_status';
    public const IMAGES = 'images';
    public const COMMENT = 'comment';

    /**
     * @param int $requestItemId
     * @return $this
     */
    public function setRequestItemId($requestItemId);

    /**
     * @return int
     */
    public function getRequestItemId();

    /**
     * @param int $requestId
     * @return $this
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param int $orderItemId
     * @return $this
     */
    public function setOrderItemId($orderItemId);

    /**
     * @return int
     */
    public function getOrderItemId();

    /**
     * @param double $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * @return double
     */
    public function getQty();

    /**
     * @param double $requestQty
     *
     * @return $this
     */
    public function setRequestQty($requestQty);

    /**
     * @return double
     */
    public function getRequestQty();

    /**
     * @param double $refundedAmount
     * @return $this
     */
    public function setRefundedAmount($refundedAmount);

    /**
     * @return double
     */
    public function getRefundedAmount();

    /**
     * @param int $reasonId
     * @return $this
     */
    public function setReasonId($reasonId);

    /**
     * @return int
     */
    public function getReasonId();

    /**
     * @param int $itemStatus
     *
     * @return $this
     */
    public function setItemStatus($itemStatus);

    /**
     * @return int
     */
    public function getItemStatus();

    /**
     * @param $images
     * @return $this
     */
    public function setImages($images);

    /**
     * @return false|string
     */
    public function getImages();

    /**
     * @param $comment
     * @return $this
     */
    public function setComment($comment);

    /**
     * @return string
     */
    public function getComment();
}
