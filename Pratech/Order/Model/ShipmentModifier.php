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

use Magento\Framework\Exception\AlreadyExistsException;
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
     * Change Shipment Status Constructor
     *
     * @param CommentFactory $shipmentCommentFactory
     * @param CommentResource $shipmentCommentResource
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CollectionFactory $collectionFactory
     * @param RefundHelper $refundHelper
     * @param Logger $apiLogger
     */
    public function __construct(
        private CommentFactory              $shipmentCommentFactory,
        private CommentResource             $shipmentCommentResource,
        private ShipmentRepositoryInterface $shipmentRepository,
        private CollectionFactory           $collectionFactory,
        private RefundHelper                $refundHelper,
        private Logger                      $apiLogger,
    ) {
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

        $statusCollection = $this->collectionFactory->create()
            ->addFieldToFilter('clickpost_status', $shipmentStatusCode);
        if ($statusCollection->getSize() > 0) {
            $status = $statusCollection->getFirstItem();
            // Storing shipment status as 12 in case of any RTO status received so that FE does not break.
            if (in_array($status->getStatusId(), RefundHelper::RTO_REFUND_CLICKPOST_STATUS)) {
                $statusId = 12;
            } else {
                $statusId = $status->getStatusId();
            }
            $shipmentDetails->setShipmentStatus($statusId);
            $shipmentComment->setComment($status->getStatus());
            $shipmentComment->setStatus($status->getStatusCode());
        } else {
            $shipmentDetails->setShipmentStatus(0);
            $shipmentComment->setComment('Status not found (Default Status: Shipped)');
            $shipmentComment->setStatus('shipped');
            $this->apiLogger->error(
                "No status found for clickpost_status_code: " . $shipmentStatusCode .
                 __METHOD__
            );
        }
        $shipmentComment->setParentId($shipmentId);
        $shipmentComment->setIsCustomerNotified(0);
        $shipmentComment->setIsVisibleOnFront(1);
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
}
