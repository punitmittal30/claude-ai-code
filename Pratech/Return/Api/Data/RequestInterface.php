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

use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\TrackingInterface;

/**
 * Interface RequestInterface
 */
interface RequestInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const REQUEST_ID = 'request_id';
    public const ORDER_ID = 'order_id';
    public const SHIPMENT_ID = 'shipment_id';
    public const CREATED_AT = 'created_at';
    public const MODIFIED_AT = 'modified_at';
    public const STATUS = 'status';
    public const REFUND_STATUS = 'refund_status';
    public const REFUNDED_AMOUNT = 'refunded_amount';
    public const REFUNDED_STORE_CREDIT = 'refunded_store_credit';
    public const IS_PROCESSED = 'is_processed';
    public const INSTANT_REFUND = 'instant_refund';
    public const CUSTOMER_ID = 'customer_id';
    public const CUSTOMER_NAME = 'customer_name';
    public const MANAGER_ID = 'manager_id';
    public const CUSTOM_FIELDS = 'custom_fields';
    public const RATING = 'rating';
    public const RATING_COMMENT = 'rating_comment';
    public const NOTE = 'note';
    public const MESSAGE = 'message';
    public const REQUEST_ITEMS = 'request_items';
    public const TRACKING_NUMBERS = 'tracking_numbers';
    public const VIN_RETURN_NUMBER = 'vin_return_number';

    public const COMMENT = 'comment';

    /**
     * @param int $requestId
     *
     * @return $this
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @param int $shipmentId
     *
     * @return $this
     */
    public function setShipmentId($shipmentId);

    /**
     * @return int
     */
    public function getShipmentId();

    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $modifiedAt
     *
     * @return $this
     */
    public function setModifiedAt(string $modifiedAt);

    /**
     * @return string
     */
    public function getModifiedAt();

    /**
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $refundStatus
     *
     * @return $this
     */
    public function setRefundStatus($refundStatus);

    /**
     * @return int
     */
    public function getRefundStatus();

    /**
     * @param bool $isProcessed
     *
     * @return $this
     */
    public function setIsProcessed($isProcessed);

    /**
     * @return bool
     */
    public function getIsProcessed();

    /**
     * @param $instantRefund
     * @return $this
     */
    public function setInstantRefund($instantRefund);

    /**
     * @return bool
     */
    public function getInstantRefund();

    /**
     * @param int $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param string $customerName
     *
     * @return $this
     */
    public function setCustomerName($customerName);

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @param int $managerId
     *
     * @return $this
     */
    public function setManagerId($managerId);

    /**
     * @return int
     */
    public function getManagerId();

    /**
     * @param int $rating
     *
     * @return $this
     */
    public function setRating($rating);

    /**
     * @return int
     */
    public function getRating();

    /**
     * @param string $ratingComment
     *
     * @return $this
     */
    public function setRatingComment($ratingComment);

    /**
     * Set Comment
     *
     * @param $comment
     * @return $this
     */
    public function setComment($comment);

    /**
     * @return string
     */
    public function getRatingComment();

    /**
     * Get Comment.
     *
     * @return string
     */
    public function getComment();

    /**
     * @param string $note
     *
     * @return $this
     */
    public function setNote($note);

    /**
     * @return string
     */
    public function getNote();

    /**
     * @param RequestItemInterface[] $requestItems
     *
     * @return $this
     */
    public function setRequestItems($requestItems);

    /**
     * @return RequestItemInterface[]
     */
    public function getRequestItems();

    /**
     * @param TrackingInterface[] $trackingNumbers
     *
     * @return $this
     */
    public function setTrackingNumbers($trackingNumbers);

    /**
     * @return TrackingInterface[]
     */
    public function getTrackingNumbers();

    /**
     * @param string $vinReturnNumber
     *
     * @return $this
     */
    public function setVinReturnNumber($vinReturnNumber);

    /**
     * @return string
     */
    public function getVinReturnNumber();

    /**
     * @param float $refundedAmount
     *
     * @return $this
     */
    public function setRefundedAmount($refundedAmount);

    /**
     * @return float
     */
    public function getRefundedAmount();

    /**
     * @param float $refundedStoreCredit
     *
     * @return $this
     */
    public function setRefundedStoreCredit($refundedStoreCredit);

    /**
     * @return float
     */
    public function getRefundedStoreCredit();
}
