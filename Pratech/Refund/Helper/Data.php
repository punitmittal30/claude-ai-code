<?php
/**
 * Pratech_Refund
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Refund
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Refund\Helper;

use DateTime;
use Exception;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Refund\Helper\Data as RefundHelper;
use Pratech\Refund\Logger\RtoRefundLogger;
use Pratech\Refund\Model\Refund;
use Pratech\Refund\Model\RefundFactory as RefundLogsFactory;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Refund Logs Helper Class
 */
class Data
{

    /**
     * Constant for RTO Refund based on Clickpost Status
     */
    public const RTO_REFUND_CLICKPOST_STATUS = [11, 12, 13, 14, 15, 16, 17, 21, 26, 27];

    /**
     * Constant for online payment methods eligible for refund.
     */
    public const PREPAID_PAYMENT_METHODS = [
        'upi',
        'netbanking',
        'card',
        'wallet',
        'online_payment_app',
        'snapmint'
    ];

    /**
     * @param RefundLogsFactory $refundLogsFactory
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param SqsEvent $sqsEvent
     * @param \Pratech\Refund\Model\ResourceModel\Refund $refundResource
     * @param Logger $apiLogger
     * @param OrderRepositoryInterface $orderRepository
     * @param BalanceFactory $balanceFactory
     * @param StoreManagerInterface $storeManager
     * @param RtoRefundLogger $rtoRefundLogger
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        private RefundLogsFactory                          $refundLogsFactory,
        private TimezoneInterface                          $timezone,
        private ScopeConfigInterface                       $scopeConfig,
        private SqsEvent                                   $sqsEvent,
        private \Pratech\Refund\Model\ResourceModel\Refund $refundResource,
        private Logger                                     $apiLogger,
        private OrderRepositoryInterface                   $orderRepository,
        private BalanceFactory                             $balanceFactory,
        private StoreManagerInterface                      $storeManager,
        private RtoRefundLogger                            $rtoRefundLogger,
        private ShipmentRepositoryInterface                $shipmentRepository
    ) {
    }

    /**
     * Trigger Refund For RTO.
     *
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @param string $eventName
     * @return float
     */
    public function triggerRefundForRto(ShipmentInterface $shipment, OrderInterface $order, string $eventName): float
    {
        $shipmentItems = $this->getShipmentItems($shipment->getItems());
        $refundAmount = $this->fetchAndStoreRefundAmountForRto($order, $shipmentItems);
        try {
            $shipment->setRefundedAmount($refundAmount);
            $this->shipmentRepository->save($shipment);
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
        $refundPayload = $this->getShipmentDataForRtoRefund($shipment, $order, $refundAmount);
        $this->rtoRefundLogger->info(
            "RTO Refund for Order ID: " . $order->getIncrementId()
            . " | Shipment ID: " . $shipment->getIncrementId(),
            [
                'refund_amount' => $refundAmount,
                'refund_payload' => $refundPayload
            ]
        );
        $this->sqsEvent->initiateRefundOnSqs($refundPayload, $eventName);
        return $refundAmount;
    }

    /**
     * Get Shipment Items.
     *
     * @param array $shipmentItems
     * @return array
     */
    private function getShipmentItems(array $shipmentItems): array
    {
        $items = [];
        foreach ($shipmentItems as $shipmentItem) {
            $items[] = [
                'name' => $shipmentItem->getName(),
                'price' => (float)$shipmentItem->getPrice(),
                'qty' => (int)$shipmentItem->getQty(),
                'sku' => $shipmentItem->getSku(),
                'product_id' => (int)$shipmentItem->getProductId(),
            ];
        }
        return $items;
    }

    /**
     * Calculate Refund Amount for RTO Shipment.
     *
     * @param OrderInterface $order
     * @param array $shipmentItems
     * @return float|int
     */
    private function fetchAndStoreRefundAmountForRto(OrderInterface $order, array $shipmentItems): float|int
    {
        try {
            $shipmentTotal = 0;
            $orderItems = $order->getItems();
            $orderTotalWithoutShipping = $order->getGrandTotal() - $order->getDeliveryCharges();
            foreach ($shipmentItems as $shipmentItem) {
                foreach ($orderItems as $orderItem) {
                    if ($orderItem->getProductId() == $shipmentItem['product_id']) {
                        $itemTotal = ($orderItem->getRowTotal() - $orderItem->getDiscountAmount())
                            * $shipmentItem['qty'] / $orderItem->getQtyOrdered();
                        $shipmentTotal += $itemTotal;
                        $orderItem->setQtyRefunded($orderItem->getQtyRefunded() + $shipmentItem['qty'])
                            ->setAmountRefunded($orderItem->getAmountRefunded() + $itemTotal)
                            ->setBaseAmountRefunded($orderItem->getBaseAmountRefunded() + $itemTotal);
                    }
                }
            }
            $refundedDeliveryCharges = $order->getDeliveryCharges() - $order->getDeliveryChargesRefunded();
            $shipmentTotal += $refundedDeliveryCharges;
            $storeCreditPoints = ($shipmentTotal / $orderTotalWithoutShipping) * $order->getCustomerBalanceAmount();
            $this->revertStoreCreditForShipment($order, $storeCreditPoints);
            $order->setTotalRefunded($order->getTotalRefunded() + $shipmentTotal)
                ->setBaseTotalRefunded($order->getBaseTotalRefunded() + $shipmentTotal)
                ->setDeliveryChargesRefunded($order->getDeliveryChargesRefunded() + $refundedDeliveryCharges)
                ->setTotalCanceled($order->getTotalCanceled() + $shipmentTotal)
                ->setBaseTotalCanceled($order->getBaseTotalCanceled() + $shipmentTotal);
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
        return $shipmentTotal;
    }

    /**
     * Revert Store Credit For Shipment.
     *
     * @param OrderInterface $order
     * @param float $storeCreditPoints
     * @return void
     * @throws NoSuchEntityException
     */
    public function revertStoreCreditForShipment(OrderInterface $order, float $storeCreditPoints): void
    {
        $conversionRate = $this->scopeConfig->getValue(
            'store_credit/store_credit/conversion_rate',
            ScopeInterface::SCOPE_STORE
        );

        $timesPointUsed = (1.0 / $conversionRate);

        $this->balanceFactory->create()->setCustomerId(
            $order->getCustomerId()
        )->setWebsiteId(
            $this->storeManager->getStore($order->getStoreId())->getWebsiteId()
        )->setAmountDelta(
            $storeCreditPoints * $timesPointUsed
        )->setHistoryAction(
            History::ACTION_REVERTED
        )->setOrder(
            $order
        )->save();

        $comment = __(
            'We refunded %1 to HCash',
            $order->getBaseCurrency()->formatTxt($storeCreditPoints)
        );
        $order->addCommentToStatusHistory($comment, $order->getStatus(), false);
    }

    /**
     * Get Shipment Data For Refund.
     *
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @param float $refundAmount
     * @return array
     */
    private function getShipmentDataForRtoRefund(
        ShipmentInterface $shipment,
        OrderInterface    $order,
        float             $refundAmount
    ): array {
        $paymentInformation = $this->getPaymentDetails($order);
        $paymentInformation['refund_amount'] = $refundAmount;
        return [
            'order_entity_id' => $shipment->getOrderId(),
            'order_increment_id' => $order->getIncrementId(),
            "customer_firstname" => $order->getCustomerFirstname(),
            "customer_lastname" => $order->getCustomerLastname(),
            "contact_information" => [
                "mobile_number" => $order->getShippingAddress()->getTelephone(),
                "email_address" => $order->getCustomerEmail()
            ],
            "payment_information" => $paymentInformation,
            "order_information" => [
                "placed_on" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "increment_id" => $order->getIncrementId()
            ],
            'shipment' => [
                'shipment_entity_id' => $shipment->getEntityId(),
                'shipment_increment_id' => $shipment->getIncrementId(),
                'shipment_status' => $shipment->getShipmentStatus(),
                'items' => $this->getShipmentItems($shipment->getItems())
            ]
        ];
    }

    /**
     * Get Payment Details
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getPaymentDetails(OrderInterface $order): array
    {
        if ($order->getPayment()) {
            $payment = $order->getPayment();
            return [
                "additional_information" => $payment->getAdditionalInformation(),
                "amount_ordered" => (float)$payment->getAmountOrdered(),
                "base_amount_ordered" => (float)$payment->getBaseAmountOrdered(),
                "method" => $payment->getMethod(),
                "rzp_order_id" => $order->getRzpOrderId(),
                "rzp_payment_id" => $order->getRzpPaymentId(),
            ];
        }
        return [];
    }

    /**
     * Get Time Based On Timezone
     *
     * @param string $date
     * @return string
     */
    public function getTimeBasedOnTimezone(string $date): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format('j M, o');
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Trigger Refund For Complete Order Cancel.
     *
     * @param OrderInterface $order
     * @param string $eventName
     * @return void
     */
    public function triggerRefundForFullOrder(OrderInterface $order, string $eventName): void
    {
        $isRefundTriggered = $this->addToRefundLogs($order, "refund.triggered");
        if ($isRefundTriggered) {
            $payload = $this->getOrderDataForRefund($order);
            $this->sqsEvent->initiateRefundOnSqs($payload, $eventName);
            $this->updateOrderForCancellation($order);
        }
    }

    /**
     * Store Refund Logs.
     *
     * @param OrderInterface $order
     * @param string $status
     * @param string|null $rzpRefundId
     * @param float|null $amount
     * @return bool
     */
    public function addToRefundLogs(
        OrderInterface $order,
        string         $status,
        string         $rzpRefundId = null,
        float          $amount = null
    ): bool {
        try {
            if (empty($this->refundResource->isRefundInitiated($order->getEntityId())) || $status == "refund.created" ||
                $status == "refund.processed" || $status == "refund.failed") {
                $this->setRefundLogs($order, $status, $rzpRefundId, $amount);
                return true;
            }
            return false;
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        return false;
    }

    /**
     * Set Refund Logs
     *
     * @param OrderInterface $order
     * @param string $status
     * @param string|null $rzpRefundId
     * @param float|null $amount
     * @return void
     * @throws Exception
     */
    private function setRefundLogs(
        OrderInterface $order,
        string         $status,
        string         $rzpRefundId = null,
        float          $amount = null
    ): void {
        /** @var Refund $refundLogs */
        $refundLogs = $this->refundLogsFactory->create();
        $refundLogs->setOrderId($order->getEntityId())
            ->setRzpOrderId($order->getRzpOrderId())
            ->setRzpPaymentId($order->getRzpPaymentId())
            ->setRzpRefundId($rzpRefundId)
            ->setStatus($status)
            ->setAmount($amount)
            ->setIncrementId($order->getIncrementId());
        $refundLogs->save();
    }

    /**
     * Get Order Data For Refund
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderDataForRefund(OrderInterface $order): array
    {
        return [
            "entity_id" => $order->getEntityId(),
            "status_code" => $order->getStatus(),
            "customer_firstname" => $order->getCustomerFirstname(),
            "customer_lastname" => $order->getCustomerLastname(),
            "contact_information" => [
                "mobile_number" => $order->getShippingAddress()->getTelephone(),
                "email_address" => $order->getCustomerEmail()
            ],
            "payment_information" => $this->getPaymentDetails($order),
            "order_information" => [
                "placed_on" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "increment_id" => $order->getIncrementId()
            ],
        ];
    }

    /**
     * Update Order For Cancellation and Payment Failed Refunds.
     *
     * @param Order $order
     * @return void
     */
    protected function updateOrderForCancellation(Order $order): void
    {
        try {
            $order->setBaseTotalCanceled($order->getBaseGrandTotal())
                ->setTotalCanceled($order->getGrandTotal())
                ->setTotalRefunded($order->getGrandTotal())
                ->setBaseTotalRefunded($order->getBaseGrandTotal());
            foreach ($order->getAllItems() as $item) {
                $item->setQtyRefunded($item->getQtyOrdered())
                    ->setAmountRefunded($item->getRowTotal())
                    ->setBaseAmountRefunded($item->getBaseRowTotal());
            }
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
    }

    /**
     * Is shipment eligible for refund.
     *
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @return bool
     */
    public function isRefundEligibleForShipment(ShipmentInterface $shipment, OrderInterface $order): bool
    {
        return in_array($shipment->getShipmentStatus(), RefundHelper::RTO_REFUND_CLICKPOST_STATUS)
            && ($shipment->getRefundedAmount() == 0 || $shipment->getRefundedAmount() == null);
    }

    /**
     * Is order eligible for complete refund.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isRefundEligibleForFullOrder(OrderInterface $order): bool
    {
        return in_array($order->getPayment()->getMethod(), self::PREPAID_PAYMENT_METHODS);
    }
}
