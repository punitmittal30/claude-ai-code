<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Api;

interface ShipmentRepositoryInterface
{
    /**
     * Get Order Shipment Details by Tracking Number
     *
     * @param string $trackNumber
     * @return array
     */
    public function getShipmentDetails(string $trackNumber): array;

    /**
     * Set Order Shipment Details by Tracking Number
     *
     * @param string $trackNumber
     * @param string $shipmentStatus
     * @return array
     */
    public function setShipmentDetails(string $trackNumber, string $shipmentStatus): array;

    /**
     * Get Order Shipment Review Form Data
     *
     * @param string $shipmentId
     * @return array
     */
    public function getShipmentReviewFormData(string $shipmentId): array;

    /**
     * Set Order Shipment Review by Shipment Id
     *
     * @param string $shipmentId
     * @param string $nickname
     * @param int $customerId
     * @param \Pratech\Order\Api\Data\ProductReviewInterface[] $productReviewData
     * @param int|null $shipmentRating
     * @param string|null $shipmentReview
     * @param string|null $shipmentKeywords
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setShipmentReview(
        string $shipmentId,
        string $nickname,
        int    $customerId,
        array  $productReviewData,
        int    $shipmentRating = null,
        string $shipmentReview = null,
        string $shipmentKeywords = null
    ): array;

    /**
     * Set Order Shipment Track Details by Tracking Number
     *
     * @param string $trackNumber
     * @param string $location
     * @param string $remark
     * @param int $clickPostStatus
     * @return array
     */
    public function setShipmentTrackDetails(
        string $trackNumber,
        string $location,
        string $remark,
        int $clickPostStatus
    ): array;
}
