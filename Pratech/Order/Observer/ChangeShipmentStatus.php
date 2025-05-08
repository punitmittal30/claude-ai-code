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

namespace Pratech\Order\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\CommentFactory;
use Pratech\Base\Logger\Logger;
use Pratech\Order\Model\ShipmentModifierFactory;

/**
 * Change Shipment Status when order is marked as shipped via admin or api.
 */
class ChangeShipmentStatus implements ObserverInterface
{
    /**
     * Shipped Status for Order Shipped
     */
    public const STATUS_SHIPPED = 'shipped';

    /**
     * Partially Shipped Status for Order Shipped
     */
    public const STATUS_PARTIALLY_SHIPPED = 'partially_shipped';

    /**
     * Change Shipment Status Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $apiLogger
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        private OrderRepositoryInterface    $orderRepository,
        private Logger                      $apiLogger,
        private ShipmentRepositoryInterface $shipmentRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();

        $orderStatusExempt = ['shipped', 'delivered', 'Delivered', 'complete'];
        if (!in_array($order->getStatus(), $orderStatusExempt)) {
            $order->setState(Order::STATE_PROCESSING)->setStatus(self::STATUS_PARTIALLY_SHIPPED);
            try {
                $shipmentDetails = $this->shipmentRepository->get($shipment->getId());
                if ($shipment->getShipmentStatus() === null) {
                    $shipmentDetails->setShipmentStatus(0);
                    $this->shipmentRepository->save($shipmentDetails);
                    $order->addCommentToStatusHistory(
                        "Vinculum: Shipment Created | Shipment ID: " . $shipment->getIncrementId()
                    );
                }
            } catch (Exception $exception) {
                $this->apiLogger->error("Error saving comment for shipment created through Vinculum
                with ID: " . $shipment->getIncrementId() . " | " . $exception->getMessage());
            }
            $this->orderRepository->save($order);
        }
    }
}
