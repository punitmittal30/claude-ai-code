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

namespace Pratech\Order\Model;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\Comment;
use Magento\Sales\Model\Order\Shipment\CommentFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Comment as CommentResource;
use Pratech\Base\Logger\Logger;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory;
use Pratech\Refund\Helper\Data as RefundHelper;

/**
 * Shipment Modifier Class to modify shipment information.
 */
class ShipmentModifier
{
    /**
     * Status code constants
     */
    private const STATUS_DELIVERED = 'delivered';

    /**
     * Change Shipment Status Constructor
     *
     * @param CommentFactory $shipmentCommentFactory
     * @param CommentResource $shipmentCommentResource
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CollectionFactory $collectionFactory
     * @param RefundHelper $refundHelper
     * @param Logger $apiLogger
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        private CommentFactory              $shipmentCommentFactory,
        private CommentResource             $shipmentCommentResource,
        private ShipmentRepositoryInterface $shipmentRepository,
        private CollectionFactory           $collectionFactory,
        private RefundHelper                $refundHelper,
        private Logger                      $apiLogger,
        private CustomerRepositoryInterface $customerRepository
    )
    {
    }

    /**
     * Update Shipment Comment.
     *
     * @param int $shipmentId
     * @param string $shipmentStatusCode
     * @return ShipmentInterface
     * @throws AlreadyExistsException
     */
    public function updateShipment(int $shipmentId, string $shipmentStatusCode): ShipmentInterface
    {
        $shipmentDetails = $this->shipmentRepository->get($shipmentId);

        /** @var Comment $shipmentComment */
        $shipmentComment = $this->shipmentCommentFactory->create();
        $shipmentComment->setParentId($shipmentId);
        $shipmentComment->setIsCustomerNotified(0);
        $shipmentComment->setIsVisibleOnFront(1);

        // Get status from collection
        $statusCollection = $this->collectionFactory->create()
            ->addFieldToFilter('clickpost_status', $shipmentStatusCode);

        if ($statusCollection->getSize() > 0) {
            $status = $statusCollection->getFirstItem();

            // Determine the proper status ID
            $statusId = in_array(
                $status->getStatusId(),
                RefundHelper::RTO_REFUND_CLICKPOST_STATUS
            ) ? 12 : (int)$status->getStatusId();

            // Update Customer Scoring Attributes if status has changed
            $this->updateCustomerScoringAttributes($shipmentDetails, $statusId, $status->getStatusCode());

            $shipmentDetails->setShipmentStatus($statusId);
            $shipmentComment->setComment($status->getStatus());
            $shipmentComment->setStatus($status->getStatusCode());
        } else {
            $shipmentDetails->setShipmentStatus(0);
            $shipmentComment->setComment('Status not found (Default Status: Shipped)');
            $shipmentComment->setStatus('shipped');
            $this->apiLogger->error(
                "No status found for clickpost_status_code: {$shipmentStatusCode} for shipment id:  {$shipmentId} " . __METHOD__
            );
        }

        $this->shipmentCommentResource->save($shipmentComment);
        $savedShipment = $this->shipmentRepository->save($shipmentDetails);
        $order = $savedShipment->getOrder();

        if ($this->refundHelper->isRefundEligibleForShipment($savedShipment, $order)) {
            $this->refundHelper->triggerRefundForRto(
                $savedShipment,
                $order,
                'CLICKPOST_RTO_REFUND'
            );
        }

        return $savedShipment;
    }

    /**
     * Update Customer Scoring Attributes
     *
     * @param ShipmentInterface $shipmentDetails
     * @param integer $statusId
     * @param string $statusCode
     * @return void
     */
    private function updateCustomerScoringAttributes(
        ShipmentInterface $shipmentDetails,
        int               $statusId,
        string            $statusCode
    ): void
    {
        // Skip if status didn't change
        if ($shipmentDetails->getShipmentStatus() == $statusId ||
            in_array($shipmentDetails->getShipmentStatus(), RefundHelper::RTO_REFUND_CLICKPOST_STATUS) ||
            $shipmentDetails->getShipmentStatus() == self::STATUS_DELIVERED
        ) {
            return;
        }

        $customerId = $shipmentDetails->getCustomerId();
        if (!$customerId) {
            return;
        }

        try {
            if ($statusCode === self::STATUS_DELIVERED) {
                $this->incrementCustomerAttribute($customerId, 'total_delivered_shipments');
            } elseif (in_array($statusId, RefundHelper::RTO_REFUND_CLICKPOST_STATUS)) {
                $this->incrementCustomerAttribute($customerId, 'total_rto_shipments');
            }
        } catch (Exception $exception) {
            $this->apiLogger->error(
                "Error saving customer scoring attributes for shipment with ID: "
                . $shipmentDetails->getIncrementId() . " | " . $exception->getMessage()
            );
        }
    }

    /**
     * Increment a customer attribute value
     *
     * @param int $customerId
     * @param string $attributeCode
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function incrementCustomerAttribute(int $customerId, string $attributeCode): void
    {
        $customerData = $this->customerRepository->getById($customerId);
        $currentValue = $customerData->getCustomAttribute($attributeCode)
            ? (int)$customerData->getCustomAttribute($attributeCode)->getValue()
            : 0;

        $customerData->setCustomAttribute($attributeCode, $currentValue + 1);
        $this->customerRepository->save($customerData);
    }
}
