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

namespace Pratech\Order\Model;

use Exception;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Pratech\Base\Logger\Logger;
use Pratech\Order\Helper\Order as OrderHelper;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as StatusCollectionFactory;
use Pratech\Order\Model\ShipmentModifier;
use Psr\Log\LoggerInterface;

/**
 * Process Packed Orders Class to process packed orders.
 */
class ProcessPackedOrders
{
    /**
     * Shipment delivered status id
     *
     * @var int|null
     */
    private $deliveredStatusId = 0;

    /**
     * Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ConvertOrder $convertOrder
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderHelper $orderHelper
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param ShipmentModifier $shipmentModifier
     * @param LoggerInterface $logger
     */
    public function __construct(
        private OrderRepositoryInterface    $orderRepository,
        private ShipmentRepositoryInterface $shipmentRepository,
        private ConvertOrder                $convertOrder,
        private CollectionFactory           $orderCollectionFactory,
        private OrderHelper                 $orderHelper,
        private StatusCollectionFactory     $statusCollectionFactory,
        private ShipmentModifier            $shipmentModifier,
        private LoggerInterface             $logger
    ) {
    }

    /**
     * Execute function to generate shipments for packed orders and mark them delivered.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $orders = $this->orderCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('status', ['eq' => 'packed'])
                ->addFieldToFilter('created_at', ['lteq' => $this->getMaxLimitDate()])
                ->getAllIds();
            foreach ($orders as $orderId) {
                $this->processOrder($orderId);
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Current Date
     *
     * @return string
     */
    public function getMaxLimitDate(): string
    {
        $today = date('Y-m-d H:i:s');
        return date("Y-m-d H:i:s", strtotime('-' . 7 . ' days', strtotime($today)));
    }

    /**
     * Generate shipment for order and mark that delivered
     *
     * @param int $orderId
     * @return void
     */
    public function processOrder(int $orderId): void
    {
        try {
            $shipmentId = $this->createShipmentForOrder($orderId);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Create shipment for order and mark it as delivered
     *
     * @param int $orderId
     * @return int
     */
    public function createShipmentForOrder(int $orderId): int
    {
        $order = $this->orderRepository->get($orderId);
        if (!$order->canShip()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("You can't create the Shipment of this order.")
            );
        }

        $orderShipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            // Check virtual item and item Quantity
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qty = $orderItem->getQtyToShip();
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $orderShipment->addItem($shipmentItem);
        }

        $orderShipment->register();
        $orderShipment->getOrder()->setIsInProcess(true);

        // Save created Order Shipment
        $orderShipment->save();
        $orderShipment->getOrder()->save();

        // Mark shipment as delivered
        if ($this->deliveredStatusId === 0) {
            $statusCollection = $this->statusCollectionFactory->create()
                ->addFieldToFilter('status_code', 'delivered');
            if ($statusCollection->getSize() > 0) {
                $this->deliveredStatusId = $statusCollection->getFirstItem()->getStatusId();
            }
        }
        $orderShipment->setShipmentStatus($this->deliveredStatusId)->save();

        $this->logger->error('Shipment ID: '.$orderShipment->getEntityId(). get_class($orderShipment));
        return $orderShipment->getEntityId();
    }
}
