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

namespace Pratech\Order\Helper;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Observer\RevertStoreCreditForOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Lock\Backend\Database;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Magento\Sales\Model\Order\Address;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Helper\Data;
use Pratech\Base\Logger\Logger;
use Pratech\Customer\Model\ResourceModel\BlockedCustomers as BlockedCustomersResource;
use Pratech\Order\Api\Data\CampaignInterface;
use Pratech\Order\Api\Data\ConfirmOrderRequestItemInterface;
use Pratech\RazorpayLogs\Model\RazorpayLogs;
use Pratech\RazorpayLogs\Model\RazorpayLogsFactory;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\Refund\Helper\Data as RefundHelper;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\SqsIntegration\Model\SqsEvent;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * Order Helper Class to power order api.
 */
class Order
{
    private const MAX_LOCK_TIME = 10;

    /**
     * Order Delivery Status Constant
     */
    public const ORDER_STATUS_DELIVERED = 'delivered';

    /**
     * Order Payment Failed Status Constant
     */
    public const ORDER_STATUS_PAYMENT_FAILED = 'payment_failed';

    /**
     * Order Processing status constant
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Order Processing status constant
     */
    public const ORDER_STATUS_PACKED = 'packed';

    /**
     * Order Pending status constant
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Max Delivery Date Constant
     */
    public const MAX_DELIVERY_DATE = 4;

    /**
     * IS CRON ENABLED FOR UPDATE PROCESSING TO PACKED STATUS
     */
    public const IS_CRON_ENABLED = 'cron_schedule/packed_order_status/status';

    /**
     * COD CONFIRM THRESHOLD AMOUNT CONFIG PATH
     */
    public const COD_CONFIRM_THRESHOLD = 'customers/cod_verification/cod_confirm_threshold';

    /**
     * COD VERIFICATION STATUS CONFIG PATH
     */
    public const COD_VERIFICATION_STATUS = 'customers/cod_verification/status';

    /**
     * Order Helper Constructor
     *
     * @param GuestCartManagementInterface $guestCartManagement
     * @param CartManagementInterface $customerCartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param DateTime $date
     * @param CartRepositoryInterface $quoteRepository
     * @param Data $baseHelper
     * @param RazorpayLogsFactory $razorpayLogsFactory
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param SqsEvent $sqsEvent
     * @param OrderManagementInterface $orderManagement
     * @param RefundHelper $refundHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param CartTotalRepositoryInterface $customerCartTotalRepository
     * @param GuestCartTotalRepositoryInterface $guestCartTotalRepository
     * @param StoreCreditHelper $storeCreditHelper
     * @param CustomerRedisCache $customerRedisCache
     * @param RevertStoreCreditForOrder $revertStoreCreditForOrder
     * @param BlockedCustomersResource $blockedCustomersResource
     * @param Database $databaseLocker
     * @param SalesOrderFactory $salesOrderFactory
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        private GuestCartManagementInterface      $guestCartManagement,
        private CartManagementInterface           $customerCartManagement,
        private CustomerRepositoryInterface       $customerRepository,
        private OrderRepositoryInterface          $orderRepository,
        private DateTime                          $date,
        private CartRepositoryInterface           $quoteRepository,
        private Data                              $baseHelper,
        private RazorpayLogsFactory               $razorpayLogsFactory,
        private Logger                            $logger,
        private StoreManagerInterface             $storeManager,
        private SqsEvent                          $sqsEvent,
        private OrderManagementInterface          $orderManagement,
        private RefundHelper                      $refundHelper,
        private ScopeConfigInterface              $scopeConfig,
        private CartTotalRepositoryInterface      $customerCartTotalRepository,
        private GuestCartTotalRepositoryInterface $guestCartTotalRepository,
        private StoreCreditHelper                 $storeCreditHelper,
        private CustomerRedisCache                $customerRedisCache,
        private RevertStoreCreditForOrder         $revertStoreCreditForOrder,
        private BlockedCustomersResource          $blockedCustomersResource,
        private Database                          $databaseLocker,
        private SalesOrderFactory                 $salesOrderFactory,
        private OrderReturnHelper                 $orderReturnHelper
    ) {
    }

    /**
     * Get Order Details
     *
     * @param int $id
     * @return array
     */
    public function getOrder(int $id): array
    {
        $order = $this->orderRepository->get($id);
        return $this->baseHelper->getOrderDetails($order);
    }

    /**
     * Cancel Order
     *
     * @param int $id
     * @param int $customerId
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function cancelOrder(int $id, int $customerId, string $reason): bool
    {
        $order = $this->orderRepository->get($id);

        // Get the current time based on the order's timezone
        $currentTime = new \DateTime($this->baseHelper->getDateTimeBasedOnTimezone('now'));

        // Check if the order was placed within the last 48 hours
        $orderCreatedAt = $order->getCreatedAt();

        $orderPlacementTime = new \DateTime($this->baseHelper->getDateTimeBasedOnTimezone($orderCreatedAt));
        $interval = $orderPlacementTime->diff($currentTime);
        $hoursElapsed = $interval->h + ($interval->days * 24);

        $cancellationTimeInHour = (int)$this->scopeConfig->getValue(
            'customers/orders/cancellation_time',
            ScopeInterface::SCOPE_STORE
        );

        if ($hoursElapsed >= $cancellationTimeInHour) {
            // Order cannot be canceled after configured hours from order placement
            return false;
        }

        if ($order->getCustomerId() == $customerId && $order->canCancel()
            && in_array($order->getStatus(), ['processing', 'pending'])) {
            $order->cancel();

            // Passing order cancellation reason in case of order cancelled by user
            $history = $order->addStatusHistoryComment($reason, $order->getStatus());
            $history->setIsVisibleOnFront(true);
            $history->save();

            $this->orderRepository->save($order);

            // Refund in case of prepaid order
            if ($this->refundHelper->isRefundEligibleForFullOrder($order)) {
                $this->refundHelper->triggerRefundForFullOrder($order, 'INITIATE_REFUND');
            }

            return true;
        }
        return false;
    }

    /**
     * Place Guest Order
     *
     * @param string $cartId
     * @param int|null $customerId
     * @param PaymentInterface|null $paymentMethod
     * @param CampaignInterface|null $campaign
     * @return array
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function placeGuestOrder(
        string            $cartId,
        int|null          $customerId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        $mageCustomerId = null;
        $eligibleCashbackAmount = 0;
        $isCustomerBlock = false;
        try {
            $customer = $this->customerRepository->getById($customerId);
            $mageCustomerId = ($customerId != null) ? $customer->getId() : null;
            $isCustomerBlock = $this->getIsCustomerBlocked($customer);
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->logger->error("Place Guest Order | Customer ID:" . $customerId .
                $e->getMessage() . __METHOD__);
        }
        if (!$isCustomerBlock) {
            try {
                $totals = $this->guestCartTotalRepository->get($cartId);
                $eligibleCashbackAmount = $this->storeCreditHelper->getCashbackAmount($totals);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
            $orderId = $this->guestCartManagement->placeOrder($cartId, $paymentMethod);
            $order = $this->orderRepository->get($orderId);
            $orderTotal = $order->getGrandTotal();
            switch ($paymentMethod->getMethod()) {
                case "cashondelivery":
                    $isEnable = $this->scopeConfig->getValue(
                        self::COD_VERIFICATION_STATUS,
                        ScopeInterface::SCOPE_STORE
                    );
                    $codThreshold = $this->scopeConfig->getValue(
                        self::COD_CONFIRM_THRESHOLD,
                        ScopeInterface::SCOPE_STORE
                    );
                    if (!$isEnable || $orderTotal < $codThreshold) {
                        $order->setStatus(SalesOrder::STATE_PROCESSING)
                            ->setState(SalesOrder::STATE_PROCESSING);
                        $order->addCommentToStatusHistory("System: Processing(processing)");
                    } else {
                        $order->setStatus(self::STATUS_PENDING)
                            ->setState(self::STATUS_PENDING);
                        $order->addCommentToStatusHistory("System: Pending(pending)");
                    }
                    break;
                case "prepaid_dpanda":
                    $order->setStatus(SalesOrder::STATE_PROCESSING)
                        ->setState(SalesOrder::STATE_PROCESSING);
                    $order->addCommentToStatusHistory("DPanda: Processing(processing)");
                    break;
                default:
                    $order->setStatus(SalesOrder::STATE_PAYMENT_REVIEW)
                        ->setState(SalesOrder::STATE_PAYMENT_REVIEW);
                    $order->addCommentToStatusHistory("System: Payment Review(payment_review)");
            }

            try {
                $order->setCustomerId($mageCustomerId);
                $order->setEstimatedDeliveryDate($this->getClickPostEdd($order->getShippingAddress()->getPostcode()));
                $order->setEligibleCashback($eligibleCashbackAmount);
                if (!empty($campaign)) {
                    $order = $this->setOrderCampaign($order, $campaign);
                }
                $order = $this->orderRepository->save($order);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage() . __METHOD__);
            }

            if ($orderId) {
                return [
                    "order_id" => $orderId,
                    "details" => $this->baseHelper->getOrderDetails($order)
                ];
            }
            return [
                "order_id" => $orderId
            ];
        }
        throw new LocalizedException(__('You are not allowed to place order due to suspicious activity.
        Please contact our customer care support for further clarifications'));
    }

    /**
     * Get Is Customer Blocked.
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function getIsCustomerBlocked(CustomerInterface $customer): bool
    {
        $mobileNumber = $customer->getCustomAttribute('mobile_number')
            ? $customer->getCustomAttribute('mobile_number')->getValue()
            : null;
        $blockedCustomers = $this->blockedCustomersResource->getBlockedCustomerByMobileNumber($mobileNumber);
        if (!empty($blockedCustomers)) {
            return true;
        }
        return false;
    }

    /**
     * Get Estimated Delivery Date
     *
     * @param string $postcode
     * @return string
     */
    private function getClickPostEdd(string $postcode): string
    {
        $days = self::MAX_DELIVERY_DATE;
        $date = $this->date->date('Y-m-d');
        return $this->date->date('Y-m-d', strtotime($date . " +" . $days . "days"));
    }

    /**
     * Update Campaign Details in sales_order table.
     *
     * @param OrderInterface $order
     * @param CampaignInterface $campaign
     * @return OrderInterface
     */
    private function setOrderCampaign(OrderInterface $order, CampaignInterface $campaign): OrderInterface
    {
        $order->setIpAddress($campaign->getIpAddress());
        $order->setPlatform($campaign->getPlatform());
        $order->setUtmId($campaign->getUtmId());
        $order->setUtmSource($campaign->getUtmSource());
        $order->setUtmCampaign($campaign->getUtmCampaign());
        $order->setUtmMedium($campaign->getUtmMedium());
        $order->setUtmTerm($campaign->getUtmTerm());
        $order->setUtmContent($campaign->getUtmContent());
        $order->setTrackerCookie($campaign->getTrackerCookie());
        $order->setUtmTimestamp($campaign->getUtmTimestamp());
        return $order;
    }

    /**
     * Place Customer Order
     *
     * @param int $cartId
     * @param PaymentInterface|null $paymentMethod
     * @param CampaignInterface|null $campaign
     * @return array
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function placeCustomerOrder(
        int               $cartId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        $isCustomerBlock = false;
        try {
            $customer = $this->quoteRepository->get($cartId)->getCustomer();
            $isCustomerBlock = $this->getIsCustomerBlocked($customer);
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Place Customer Order | Cart ID:" . $cartId .
                $e->getMessage() . __METHOD__);
        }
        if (!$isCustomerBlock) {
            $eligibleCashbackAmount = 0;
            try {
                $totals = $this->customerCartTotalRepository->get($cartId);
                $eligibleCashbackAmount = $this->storeCreditHelper->getCashbackAmount($totals);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
            $orderId = $this->customerCartManagement->placeOrder($cartId, $paymentMethod);
            $order = $this->orderRepository->get($orderId);
            $orderTotal = $order->getGrandTotal();
            switch ($paymentMethod->getMethod()) {
                case "free":
                case "cashondelivery":
                    $isEnable = $this->scopeConfig->getValue(
                        self::COD_VERIFICATION_STATUS,
                        ScopeInterface::SCOPE_STORE
                    );
                    $codThreshold = $this->scopeConfig->getValue(
                        self::COD_CONFIRM_THRESHOLD,
                        ScopeInterface::SCOPE_STORE
                    );
                    if (!$isEnable || $orderTotal < $codThreshold) {
                        $order->setStatus(SalesOrder::STATE_PROCESSING)
                            ->setState(SalesOrder::STATE_PROCESSING);
                        $order->addCommentToStatusHistory("System: Processing(processing)");
                    } else {
                        $order->setStatus(self::STATUS_PENDING)
                            ->setState(self::STATUS_PENDING);
                        $order->addCommentToStatusHistory("System: Pending(pending)");
                    }
                    break;
                case "prepaid_dpanda":
                    $order->setStatus(SalesOrder::STATE_PROCESSING)
                        ->setState(SalesOrder::STATE_PROCESSING);
                    $order->addCommentToStatusHistory("DPanda: Processing(processing)");
                    break;
                default:
                    $order->setStatus(SalesOrder::STATE_PAYMENT_REVIEW)
                        ->setState(SalesOrder::STATE_PAYMENT_REVIEW);
                    $order->addCommentToStatusHistory("System: Payment Review(payment_review)");
            }
            $order->setEstimatedDeliveryDate($this->getClickPostEdd($order->getShippingAddress()->getPostcode()));
            $order->setEligibleCashback($eligibleCashbackAmount);
            if (!empty($campaign)) {
                $order = $this->setOrderCampaign($order, $campaign);
            }
            $this->orderRepository->save($order);
            if ($orderId) {
                return [
                    "order_id" => $orderId,
                    "details" => $this->baseHelper->getOrderDetails($this->orderRepository->get($orderId))
                ];
            }
            return [
                "order_id" => $orderId
            ];
        }
        throw new LocalizedException(__(
            'Your account is suspended due to suspicious activity. Contact customer support for help.'
        ));
    }

    /**
     * Confirm order
     *
     * @param ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @return array
     */
    public function confirmOrder(ConfirmOrderRequestItemInterface $confirmOrderRequest): array
    {
        try {
            return match ($confirmOrderRequest->getStatus()) {
                'processing' => $this->setOrderSuccess($confirmOrderRequest),
                'payment_failed' => $this->setOrderFailure($confirmOrderRequest),
                default => $this->setOrderStatusMismatched($confirmOrderRequest),
            };
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [
                "is_confirmed" => false,
                "message" => "Something went wrong. Please contact customer service executive."
            ];
        }
    }

    /**
     * Update Order Table on payment success
     *
     * @param ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @return array
     * @throws Exception
     */
    private function setOrderSuccess(ConfirmOrderRequestItemInterface $confirmOrderRequest): array
    {
        $order = $this->orderRepository->get($confirmOrderRequest->getOrderId());
        switch ($order->getStatus()) {
            case 'pending':
            case 'payment_review':
                $order->setStatus(SalesOrder::STATE_PROCESSING)
                    ->setState(SalesOrder::STATE_PROCESSING);
                $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
                if ($paymentMethod == 'bnpl') {
                    $order->addCommentToStatusHistory(
                        "API : Processing(processing)"
                        . " | Source : " . $confirmOrderRequest->getSource()
                        . " | Payment By BNPL"
                    );
                } elseif ($paymentMethod == 'snapmint') {
                    $order->addCommentToStatusHistory(
                        "API : Processing(processing)"
                        . " | Source : " . $confirmOrderRequest->getSource()
                        . " | Payment By Snapmint"
                    );
                } else {
                    $order->addCommentToStatusHistory(
                        "API : Processing(processing)"
                        . " | Source : " . $confirmOrderRequest->getSource()
                        . " | Razorpay Order ID : " . $confirmOrderRequest->getRzpOrderId()
                        . " | Razorpay Payment ID : " . $confirmOrderRequest->getRzpPaymentId()
                    );
                }
                $payment = $this->setOrderPayment($order, $order->getPayment());
                $order->setPayment($payment);
                $order->setIsConfirmed(1);
                $order->setRzpOrderId($confirmOrderRequest->getRzpOrderId());
                $order->setRzpPaymentId($confirmOrderRequest->getRzpPaymentId());
                $this->orderRepository->save($order);
                break;
            case 'processing':
                $this->addToRazorpayLogs($confirmOrderRequest, $order, "Log");
                break;
            case 'payment_failed':
                $this->addToRazorpayLogs($confirmOrderRequest, $order, "Critical");
                $this->refundHelper->triggerRefundForFullOrder($order, 'INITIATE_REFUND');
                $emailData = $this->getOrderDataForEmail($order, 'PAYMENT_FAILED');
                $this->sqsEvent->sentEmailEventToSqs($emailData);
                break;
            default:
                $this->addToRazorpayLogs($confirmOrderRequest, $order, "Critical");
                break;
        }
        return [
            "is_confirmed" => true,
            "message" => "Payment Successful"
        ];
    }

    /**
     * Update Payment Details in sales_order_payment table.
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentInterface
     */
    private function setOrderPayment(OrderInterface $order, OrderPaymentInterface $payment): OrderPaymentInterface
    {
        $payment->setAmountPaid($order->getPayment()->getAmountOrdered());
        $payment->setBaseAmountPaid($order->getPayment()->getBaseAmountOrdered());
        $payment->setBaseAmountPaidOnline($order->getPayment()->getBaseAmountPaidOnline());
        return $payment;
    }

    /**
     * Store Razorpay Payment Logs.
     *
     * @param ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @param OrderInterface $order
     * @param string $classification
     * @return void
     */
    public function addToRazorpayLogs(
        ConfirmOrderRequestItemInterface $confirmOrderRequest,
        OrderInterface                   $order,
        string                           $classification
    ): void {
        try {
            /** @var RazorpayLogs $razorpayLogs */
            $razorpayLogs = $this->razorpayLogsFactory->create();
            $razorpayLogs->setOrderId($confirmOrderRequest->getOrderId());
            $razorpayLogs->setRzpOrderId($confirmOrderRequest->getRzpOrderId());
            $razorpayLogs->setRzpPaymentId($confirmOrderRequest->getRzpPaymentId());
            $razorpayLogs->setSource($confirmOrderRequest->getSource());
            $razorpayLogs->setStatus($confirmOrderRequest->getStatus());
            $razorpayLogs->setIncrementId($order->getIncrementId());
            $razorpayLogs->setClassification($classification);
            $razorpayLogs->save();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Order Data For Email Event
     *
     * @param SalesOrder $order
     * @param string $eventName
     * @return array
     * @throws LocalizedException
     */
    private function getOrderDataForEmail(SalesOrder $order, string $eventName): array
    {
        $shippingAddress = $order->getShippingAddress();
        return [
            'type' => 'email',
            'event_name' => $eventName,
            'name' => ucfirst($shippingAddress->getFirstname()) . " " . ucfirst($shippingAddress->getLastname()),
            'email' => $shippingAddress->getEmail(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone(),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'shipping_address' => $this->getShippingAddressData($shippingAddress),
            'order_placement_date' => $this->baseHelper
                ->getDateTimeBasedOnTimezone($order->getCreatedAt(), 'd/m/y H:i:s'),
            'items' => $this->getOrderItemsData($order),
            'mrp_total' => number_format($order->getMrpTotal(), 2),
            'bag_discount' => $order->getBagDiscount() ? number_format($order->getBagDiscount(), 2) : 0,
            'shipping_amount' => number_format($order->getDeliveryCharges() ? $order->getDeliveryCharges() : 0, 2),
            'discount' => number_format($order->getBaseDiscountAmount(), 2),
            'prepaid_discount' => number_format($order->getPrepaidDiscount() ? $order->getPrepaidDiscount() : 0, 2),
            'grand_total_without_prepaid' => number_format($order->getGrandTotalWithoutPrepaid()
                ? $order->getGrandTotalWithoutPrepaid() : 0, 2),
            'grand_total' => number_format($order->getGrandTotal(), 2),
            'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : ''
        ];
    }

    /**
     * Get Shipping Address Data
     *
     * @param Address $shippingAddress
     * @return string
     */
    private function getShippingAddressData(Address $shippingAddress): string
    {
        return implode(', ', $shippingAddress->getStreet()) . ", " . $shippingAddress->getCity() . ", "
            . $shippingAddress->getRegion() . " - " . $shippingAddress->getPostcode();
    }

    /**
     * Get Order Items Data
     *
     * @param SalesOrder $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function getOrderItemsData(SalesOrder $order): array
    {
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $orderItems = [];
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            $orderItems[] = [
                'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                'name' => $item->getName(),
                'qty' => (int)$item->getQtyOrdered(),
                'cost' => $item->getBaseCost() ? number_format($item->getBaseCost(), 2) : 0,
                'price' => $item->getPrice() ? number_format($item->getPrice(), 2) : 0,
                'brand' => $product->getCustomAttribute('brand') ?
                    $this->baseHelper->getProductAttributeLabel(
                        'brand',
                        $product->getCustomAttribute('brand')->getValue()
                    ) : "",
                'primary_l1_category' => $product->getCustomAttribute('primary_l1_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l1_category')->getValue()
                    ) : "",
                'primary_l2_category' => $product->getCustomAttribute('primary_l2_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l2_category')->getValue()
                    ) : "",
            ];
        }
        return $orderItems;
    }

    /**
     * Update Order Table on payment failure
     *
     * @param ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @return array
     * @throws Exception
     */
    private function setOrderFailure(ConfirmOrderRequestItemInterface $confirmOrderRequest): array
    {
        $lockName = 'api_key_' . $confirmOrderRequest->getOrderId();

        try {
            if (!$this->databaseLocker->lock($lockName, self::MAX_LOCK_TIME)) {
                return [
                    "is_confirmed" => false,
                    "message" => "Payment Failed - Process already in progress"
                ];
            }

            $order = $this->orderRepository->get($confirmOrderRequest->getOrderId());
            switch ($order->getStatus()) {
                case 'pending':
                case 'payment_review':
                    try {
                        $failedOrder = $this->cancelOrderItems($order);
                        $failedOrder->addCommentToStatusHistory(
                            "API : Payment Failed(payment_failed)"
                            . " | Source : " . $confirmOrderRequest->getSource()
                            . " | Razorpay Order ID : " . $confirmOrderRequest->getRzpOrderId()
                            . " | Razorpay Payment ID : " . $confirmOrderRequest->getRzpPaymentId(),
                            $order->getStatus(),
                            true
                        );
                        $failedOrder->setIsConfirmed(0);
                        $failedOrder->setRzpOrderId($confirmOrderRequest->getRzpOrderId());
                        $failedOrder->setRzpPaymentId($confirmOrderRequest->getRzpPaymentId());
                        $this->orderRepository->save($failedOrder);
                        $this->revertStoreCreditForOrder->execute($order);
                        $this->customerRedisCache->deleteCustomerStoreCreditTransactions($order->getCustomerId());
                    } catch (Exception $exception) {
                        $this->logger->error($exception->getMessage() . __METHOD__);
                        throw $exception; // Re-throw to ensure lock is released
                    }
                    break;
                case 'payment_failed':
                    $this->addToRazorpayLogs($confirmOrderRequest, $order, "Info");
                    break;
                default:
                    $this->addToRazorpayLogs($confirmOrderRequest, $order, "Critical");
                    break;
            }
            return [
                "is_confirmed" => false,
                "message" => "Payment Failed"
            ];
        } finally {
            if ($this->databaseLocker->isLocked($lockName)) {
                $this->databaseLocker->unlock($lockName);
            }
        }
    }

    /**
     * Cancel Order Items.
     *
     * @param SalesOrder $order
     * @return SalesOrder
     */
    public function cancelOrderItems(SalesOrder $order): SalesOrder
    {
        try {
            if ($order->canCancel() || $order->isPaymentReview() || $order->isFraudDetected()) {
                $state = SalesOrder::STATE_CANCELED;
                foreach ($order->getAllItems() as $item) {
                    if ($state != SalesOrder::STATE_PROCESSING && $item->getQtyToRefund()) {
                        if ($item->isProcessingAvailable()) {
                            $state = SalesOrder::STATE_PROCESSING;
                        } else {
                            $state = SalesOrder::STATE_COMPLETE;
                        }
                    }
                    $item->cancel();
                }

                $order->setSubtotalCanceled($order->getSubtotal() - $order->getSubtotalInvoiced());
                $order->setBaseSubtotalCanceled($order->getBaseSubtotal() - $order->getBaseSubtotalInvoiced());

                $order->setTaxCanceled($order->getTaxAmount() - $order->getTaxInvoiced());
                $order->setBaseTaxCanceled($order->getBaseTaxAmount() - $order->getBaseTaxInvoiced());

                $order->setShippingCanceled($order->getShippingAmount() - $order->getShippingInvoiced());
                $order->setBaseShippingCanceled($order->getBaseShippingAmount() - $order->getBaseShippingInvoiced());

                $order->setDiscountCanceled(abs((float)$order->getDiscountAmount()) - $order->getDiscountInvoiced());
                $order->setBaseDiscountCanceled(
                    abs((float)$order->getBaseDiscountAmount()) - $order->getBaseDiscountInvoiced()
                );

                $order->setTotalCanceled($order->getGrandTotal() - $order->getTotalPaid());
                $order->setBaseTotalCanceled($order->getBaseGrandTotal() - $order->getBaseTotalPaid());

                $order->setState($state)
                    ->setStatus(self::ORDER_STATUS_PAYMENT_FAILED);

                return $order;
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $order;
    }

    /**
     * Update Order Table on status mismatch
     *
     * @param ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @return array
     * @throws Exception
     */
    private function setOrderStatusMismatched(ConfirmOrderRequestItemInterface $confirmOrderRequest): array
    {
        $order = $this->orderRepository->get($confirmOrderRequest->getOrderId());
        $order->setIsConfirmed(0);
        $order->addCommentToStatusHistory("System: Order Status Mismatched");
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1);
        $this->quoteRepository->save($quote);
        $order->save();
        return [
            "is_confirmed" => false,
            "message" => "Order Status Mismatched"
        ];
    }

    /**
     * Confirm COD order
     *
     * @param int $orderId
     * @param string $source
     * @return array
     */
    public function confirmCodOrder(int $orderId, string $source): array
    {
        try {
            $order = $this->salesOrderFactory->create()
                ->loadByIncrementId($orderId);

            if ($order->getStatus() == self::STATUS_PENDING) {
                $order->setStatus(SalesOrder::STATE_PROCESSING)
                    ->setState(SalesOrder::STATE_PROCESSING)
                    ->setIsConfirmed(1);
                $order->addCommentToStatusHistory(
                    "API : Processing(processing)"
                    . " | Source : " . $source
                );
                $this->orderRepository->save($order);
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [
                "is_confirmed" => false,
                "message" => "Something went wrong. Please contact customer service executive."
            ];
        }
        return [
            "is_confirmed" => true,
            "message" => "Order Confirm Successful"
        ];
    }

    /**
     * Mark order as delivered.
     *
     * @param int $orderId
     * @return bool
     */
    public function deliverOrder(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        $order->addCommentToStatusHistory("Vinculum: Delivered(complete)");
        $order->setStatus(self::ORDER_STATUS_DELIVERED)
            ->setState(SalesOrder::STATE_COMPLETE);
        $this->orderRepository->save($order);
        return true;
    }

    /**
     * Add Order Comment.
     *
     * @param int $id
     * @param OrderStatusHistoryInterface $statusHistory
     * @param mixed $refund
     * @return true
     */
    public function addOrderComment(int $id, OrderStatusHistoryInterface $statusHistory, mixed $refund): bool
    {
        $order = $this->orderRepository->get($id);
        $statusHistory->setStatus($order->getStatus());
        $isCommentAdded = $this->orderManagement->addComment($id, $statusHistory);
        if (isset($refund)) {
            $refundAmount = $refund['refund_amount'] ?? null;
            $refundId = $refund['refund_id'] ?? null;
            $this->refundHelper->addToRefundLogs(
                $order,
                $refund['status'],
                $refundId,
                $refundAmount
            );
        }
        return $isCommentAdded;
    }

    /**
     * Mark order as item(s) packed in warehouse.
     *
     * @param int $orderId
     * @return bool|string
     */
    public function packedOrder(int $orderId): bool|string
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $order = $this->orderRepository->get($orderId);
            $status = $order->getStatus();

            // Check if the order status is not 'Processing'
            if ($status !== self::STATUS_PROCESSING) {
                return "This order is in {$status} status. Packed status can only be applied to orders
                in 'Processing' status.";
            }

            $order->addCommentToStatusHistory("Vinculum: Item(s) packed in warehouse");
            $order->setStatus(self::ORDER_STATUS_PACKED)
                ->setState(SalesOrder::STATE_PROCESSING);
            $this->orderRepository->save($order);
            return true;
        }
        return false;
    }

    /**
     * Cancel Partial Order.
     *
     * @param int $orderId
     * @param array $items
     * @return bool
     */
    public function cancelPartialOrder(int $orderId, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $commentText = '';
        $itemsCancelData = [];
        try {
            foreach ($items as $requestItem) {
                $orderItemId = $requestItem['order_item_id'];
                $reason = $requestItem['reason'] ?? '';
                $itemsCancelData['items'][$orderItemId] = [
                    'cancel' => 1,
                    'cancel_qty' => $requestItem['cancel_qty'],
                    'reason' => $reason
                ];
                if (!empty($reason)) {
                    $commentText .= (' ' . $reason);
                }
            }
            $itemsCancelData['comment_text'] = trim($commentText);
            $this->processPartialOrderCancellation($orderId, $itemsCancelData);

            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }

        return false;
    }

    /**
     * Process Partial Order Cancellation.
     *
     * @param int $orderId
     * @param array $itemsCancelData
     * @return void
     */
    public function processPartialOrderCancellation(int $orderId, array $itemsCancelData): void
    {
        $order = $this->orderRepository->get($orderId);
        if ($this->cancelPartialOrderItems($order, $itemsCancelData)) {
            $smsData = $this->getPartialOrderDataForSQS($order, 'sms', $itemsCancelData);
            $this->sqsEvent->sentSmsEventToSqs($smsData);
            $emailData = $this->getPartialOrderDataForSQS($order, 'email', $itemsCancelData);
            $this->sqsEvent->sentEmailEventToSqs($emailData);

            $this->triggerRefundForCancelledItems($orderId, $itemsCancelData);
        }
    }

    /**
     * Cancel Partial Order Items.
     *
     * @param SalesOrder $order
     * @param array $itemsCancelData
     * @return bool
     */
    public function cancelPartialOrderItems(SalesOrder $order, array $itemsCancelData): bool
    {
        try {
            $comment = $itemsCancelData['comment_text'] ?? '';
            $itemsToCancel = $this->getCancelItemIds($itemsCancelData);
            if ($order->canCancel() || $order->isPaymentReview() || $order->isFraudDetected()) {
                $itemsRowCanceled = $itemsBaseRowCanceled = 0;
                $itemsTaxCanceled = $itemsBaseTaxCanceled = 0;
                $itemsDiscountCanceled = $itemsBaseDiscountCanceled = 0;

                foreach ($order->getAllItems() as $item) {
                    if (!in_array($item->getItemId(), $itemsToCancel)) {
                        continue;
                    }
                    $qtyOrdered = $item->getQtyOrdered() ?? 0;
                    $qtyCanceled = $item->getQtyCanceled() ?? 0;
                    $canCancelItem = $qtyOrdered - $qtyCanceled > 0 ? true : false;
                    if (!$canCancelItem) {
                        continue;
                    }

                    $itemsRowCanceled += ($item->getRowTotal() - $item->getRowInvoiced());
                    $itemsBaseRowCanceled += ($item->getBaseRowTotal() - $item->getBaseRowInvoiced());
                    $itemsTaxCanceled += ($item->getTaxAmount() - $item->getTaxInvoiced());
                    $itemsBaseTaxCanceled += ($item->getBaseTaxAmount() - $item->getBaseTaxInvoiced());
                    $itemsDiscountCanceled += (abs($item->getDiscountAmount() ?? 0) - $item->getDiscountInvoiced());
                    $itemsBaseDiscountCanceled +=
                    (abs($item->getBaseDiscountAmount() ?? 0) - $item->getBaseDiscountInvoiced());

                    $item->cancel();
                }

                $order->setSubtotalCanceled($order->getSubtotalCanceled() + $itemsRowCanceled);
                $order->setBaseSubtotalCanceled($order->getBaseSubtotalCanceled() + $itemsBaseRowCanceled);

                $order->setTaxCanceled($order->getTaxCanceled() + $itemsTaxCanceled);
                $order->setBaseTaxCanceled($order->getBaseTaxCanceled() + $itemsBaseTaxCanceled);

                $order->setDiscountCanceled(abs($order->getDiscountCanceled() ?? 0) + $itemsDiscountCanceled);
                $order->setBaseDiscountCanceled(
                    abs($order->getBaseDiscountCanceled() ?? 0) + $itemsBaseDiscountCanceled
                );

                $order->setTotalCanceled($order->getTotalCanceled() + $itemsRowCanceled - $itemsDiscountCanceled);
                $order->setBaseTotalCanceled(
                    $order->getBaseTotalCanceled() + $itemsBaseRowCanceled - $itemsBaseDiscountCanceled
                );

                if (!empty($comment)) {
                    $order->addStatusHistoryComment($comment, false);
                }
                $this->orderRepository->save($order);
            }
            return true;
        } catch (Exception $exception) {
            $this->logger->error(
                "Partial Order Cancellation Issue From Core"
                . " | Order Entity ID: " . $order->getId()
                . " | Order Item IDs: " . implode(',', $itemsToCancel)
                . " | " . $exception->getMessage()
            );
        }
        return false;
    }

    /**
     * Get Cancel Item Ids
     *
     * @param array $itemsCancelData
     * @return array
     */
    private function getCancelItemIds(array $itemsCancelData)
    {
        $items = $itemsCancelData['items'];
        $itemsToCancel = [];
        foreach ($items as $key => $itemArray) {
            if (!empty($itemArray['cancel'])) {
                $itemsToCancel[] = $key;
            }
        }
        return $itemsToCancel;
    }

    /**
     * Get Order Data For Sqs Event
     *
     * @param SalesOrder $order
     * @param string $type
     * @param array $itemsCancelData
     * @return array
     */
    private function getPartialOrderDataForSQS(SalesOrder $order, string $type, array $itemsCancelData): array
    {
        $cancelledItemsArray = [];
        $comment = $itemsCancelData['comment_text'] ?? '';
        $itemsToCancel = $this->getCancelItemIds($itemsCancelData);
        foreach ($order->getAllItems() as $item) {
            if (!in_array($item->getItemId(), $itemsToCancel)) {
                continue;
            }
            $cancelledItemsArray[] = [
                'order_item_id' => (int)$item->getItemId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'qty' => (int)$item->getQtyOrdered(),
                'product_id' => (int)$item->getProductId(),
                'reason' => $itemsCancelData['items'][$item->getItemId()]['reason'] ?? $comment
            ];
        }
        $shippingAddress = $order->getShippingAddress();
        $paymentInformation = [
            'method' => $order->getPayment()->getMethod(),
            'title' => $order->getPayment()->getMethodInstance()->getTitle()
        ];
        return [
            'type' => $type,
            'event_name' => 'PARTIAL_ORDER_CANCELLED',
            'name' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone(),
            'email' => $shippingAddress->getEmail(),
            'items' => $cancelledItemsArray,
            'cancellation_reason' => $comment,
            'payment_information' => $paymentInformation
        ];
    }

    /**
     * Trigger Refund for Cancelled Items.
     *
     * @param int $orderId
     * @param array $itemsCancelData
     * @return float
     */
    public function triggerRefundForCancelledItems(int $orderId, array $itemsCancelData): float
    {
        try {
            $order = $this->orderRepository->get($orderId);

            if ($order->getPayment()->getMethod() === 'cashondelivery') {
                return false;
            }

            $refundTotal = 0;
            $comment = $itemsCancelData['comment_text'] ?? '';
            $cancelledItemsData = [];
            $itemsToRefund = $this->getCancelItemIds($itemsCancelData);
            foreach ($order->getAllItems() as $item) {
                if (!in_array($item->getItemId(), $itemsToRefund)) {
                    continue;
                }

                $refundTotal += ($item->getRowTotal() - $item->getDiscountAmount());
                $cancelledItemsData[] = [
                    'order_item_id' => (int)$item->getItemId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'cancelled_qty' => (int)$item->getQtyOrdered(),
                    'product_id' => (int)$item->getProductId(),
                    'reason' => $itemsCancelData['items'][$item->getItemId()]['reason'] ?? $comment
                ];
            }
            $orderTotalWithoutShipping = $order->getGrandTotal() - $order->getDeliveryCharges();
            $refundedStoreCredit = ($refundTotal / $orderTotalWithoutShipping) * $order->getCustomerBalanceAmount();

            $refundAmount = $this->updateOrderRefundTotals($order, $itemsToRefund);

            // Revert H Cash for return
            $this->orderReturnHelper->revertStoreCreditForReturnRequest($order, $refundedStoreCredit);

            $this->refundHelper->addToRefundLogs($order, "refund.triggered");
            $refundPayload = $this->getRefundDataForCancelledItems($cancelledItemsData, $order, $refundAmount);
            $this->logger->info(
                "Partial Cancellation Refund for Order ID: " . $order->getIncrementId(),
                [
                    'refund_amount' => $refundAmount,
                    'refund_payload' => $refundPayload
                ]
            );
            $this->sqsEvent->initiateRefundOnSqs($refundPayload, 'INITIATE_REFUND');

            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }

        return false;
    }

    /**
     * Calculate Refund Amount for Partial Cancel Request.
     *
     * @param SalesOrder $order
     * @param array $itemsToRefund
     * @return float|int
     */
    private function updateOrderRefundTotals(SalesOrder $order, array $itemsToRefund): float|int
    {
        try {
            $refundTotal = 0;
            foreach ($order->getAllItems() as $item) {
                if (!in_array($item->getItemId(), $itemsToRefund)) {
                    continue;
                }

                $qtyToRefund = (int)$item->getQtyOrdered();
                $itemRefundAmount = $this->getRefundedAmountByItemId($order, (int)$item->getItemId(), $qtyToRefund);
                $refundTotal += $itemRefundAmount;

                $item->setQtyRefunded($item->getQtyRefunded() + $qtyToRefund)
                    ->setAmountRefunded(
                        $item->getAmountRefunded() + $itemRefundAmount
                    )
                    ->setBaseAmountRefunded(
                        $item->getBaseAmountRefunded() + $itemRefundAmount
                    );
            }

            // Update order refund values
            $order->setTotalRefunded($order->getTotalRefunded() + $refundTotal)
                ->setBaseTotalRefunded($order->getBaseTotalRefunded() + $refundTotal);

            $this->orderRepository->save($order);
            return $refundTotal;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return 0;
        }
    }

    /**
     * Get the refunded amount for a specific order item ID.
     *
     * @param SalesOrder $order
     * @param int $orderItemId
     * @param int $qtyToRefund
     * @return float
     */
    private function getRefundedAmountByItemId(SalesOrder $order, int $orderItemId, int $qtyToRefund): float
    {
        foreach ($order->getAllItems() as $orderItem) {
            if ((int)$orderItem->getId() === $orderItemId) {
                $totalAfterDiscount = max(0, $orderItem->getRowTotal() - $orderItem->getDiscountAmount());
                $refundedAmount = ($totalAfterDiscount * $qtyToRefund) / max(1, $orderItem->getQtyOrdered());
                return round($refundedAmount, 2);
            }
        }
        return 0;
    }

    /**
     * Prepare refund data for cancelled items.
     *
     * @param array $cancelledItemsData
     * @param SalesOrder $order
     * @param float $refundAmount
     * @return array
     */
    private function getRefundDataForCancelledItems(
        array $cancelledItemsData,
        SalesOrder $order,
        float $refundAmount
    ): array {
        $orderDataForRefund = $this->refundHelper->getOrderDataForRefund($order);
        $orderDataForRefund['payment_information']['refund_amount'] = $refundAmount;
        $orderDataForRefund['cancel_request'] = [
            'items' => $cancelledItemsData
        ];
        return $orderDataForRefund;
    }
}
