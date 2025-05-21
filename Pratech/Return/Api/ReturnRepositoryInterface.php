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

namespace Pratech\Return\Api;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\ReturnItemInterface;
use Pratech\Return\Api\Data\PaymentDetailsInterface;

interface ReturnRepositoryInterface
{
    /**
     * Loads a return reasons.
     *
     * @return array
     */
    public function getReturnReasons(): array;

    /**
     * Loads a return status list.
     *
     * @return array
     */
    public function getReturnStatus(): array;

    /**
     * Create Order Return Request
     *
     * @param int $shipmentId
     * @param ReturnItemInterface[] $items
     * @param string|null $comment
     * @param PaymentDetailsInterface|null $paymentDetails
     * @return array
     * @throws Exception
     */
    public function createReturnRequest(
        int    $shipmentId,
        array  $items = [],
        string $comment = null,
        ?PaymentDetailsInterface $paymentDetails = null
    ): array;

    /**
     * Get Return Request Details
     *
     * @param  int $requestId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReturnRequest(int $requestId): array;

    /**
     * Update Return Status by Tracking number
     *
     * @param  string $trackNumber
     * @param  string $returnStatus
     * @return array
     */
    public function updateReturnStatus(string $trackNumber, string $returnStatus): array;

    /**
     * Update Return Refund Status by Request Id
     *
     * @param  int    $requestId
     * @param  string $refundStatus
     * @return array
     */
    public function updateRefundStatus(int $requestId, string $refundStatus): array;

    /**
     * Set Order Return Track Details by Tracking Number
     *
     * @param  string $trackNumber
     * @param  string $location
     * @param  string $remark
     * @param  int    $clickPostStatus
     * @return array
     */
    public function setReturnTrackDetails(
        string $trackNumber,
        string $location,
        string $remark,
        int    $clickPostStatus
    ): array;

    /**
     * Cancel Order Return Request
     *
     * @param  int $requestId
     * @return array
     * @throws Exception
     */
    public function cancelReturnRequest(
        int    $requestId
    ): array;
}
