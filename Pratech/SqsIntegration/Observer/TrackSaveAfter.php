<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SqsIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Pratech\SqsIntegration\Model\SqsEvent;

class TrackSaveAfter implements ObserverInterface
{
    /**
     * @param SqsEvent $sqsEvent
     * @param Logger $apiLogger
     * @param BaseHelper $baseHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private SqsEvent   $sqsEvent,
        private Logger     $apiLogger,
        private BaseHelper $baseHelper,
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Track $track
         */
        $track = $observer->getEvent()->getDataObject();
        $awbNumber = $track->getTrackNumber();

        try {
            if ($awbNumber) {
                $shipment = $track->getShipment();
                $orderId = $shipment->getOrderId();

                $order = $this->orderRepository->get($orderId);
                $shippingAddress = $order->getShippingAddress();

                $shipmentsCollection = $order->getShipmentsCollection();
                $shipmentsCollection->clear()->load(); 

                $trackingDetails = [];
                foreach ($shipmentsCollection as $shipment) {
                    foreach ($shipment->getAllTracks() as $shipmentTrack) {
                        $trackingDetails[] = [
                            'shipment_id' => $shipment->getIncrementId(),
                            'awb_number' => $shipmentTrack->getTrackNumber(),
                            'title' => $shipmentTrack->getTitle(),
                            'items' => $this->getOrderItemsData($shipment),
                        ];
                    }
                }

                $messageData = [
                    'type' => 'event',
                    'event_name' => 'AWB_REGISTERED',
                    'id' => $order->getId(),
                    'order_id' => $order->getIncrementId(),
                    'email' => $shippingAddress->getEmail(),
                    'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                    'phone_number' => $shippingAddress->getTelephone(),
                    'carrier_code' => $track->getCarrierCode(),
                    'title' => $track->getTitle(),
                    'created_at' => $this->baseHelper->getDateTimeBasedOnTimezone($track->getCreatedAt(), 'd/m/y H:i:s'),
                    'tracking' => $trackingDetails,
                ];

                $this->sqsEvent->sentEmailEventToSqs($messageData);
            }
        } catch (Exception $e) {
            $this->apiLogger->info('TrackSaveAfter Error: ' . $e->getMessage());
        }
    }

    /**
     * Get Order Items Data
     *
     * @param Shipment $shipment
     * @return array
     * @throws NoSuchEntityException
     */
    private function getOrderItemsData(Shipment $shipment): array
    {
        $shipmentItems = [];
        foreach ($shipment->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $shipmentItems[] = [
                'name' => $orderItem->getName(),
                'qty' => (int)$item->getQty(),
                'cost' => $orderItem->getBaseCost() ? number_format($orderItem->getBaseCost(), 2) : 0,
                'price' => $orderItem->getPrice() ? number_format($orderItem->getPrice(), 2) : 0,
                'sku' => $orderItem->getSku()
            ];
        }
        return $shipmentItems;
    }
}
