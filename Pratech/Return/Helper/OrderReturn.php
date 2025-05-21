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

namespace Pratech\Return\Helper;

use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as ShipmentStatusCollectionFactory;
use Pratech\Return\Api\CreateReturnProcessorInterface;
use Pratech\Return\Api\Data\PaymentDetailsInterface;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\ReturnItemInterface;
use Pratech\Return\Api\Data\ReturnOrderItemInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Logger\ReturnRefundLogger;
use Pratech\Return\Model\History\ResourceModel\CollectionFactory as HistoryCollectionFactory;
use Pratech\Return\Model\OptionSource\ItemStatus;
use Pratech\Return\Model\Reason\ReasonFactory;
use Pratech\Return\Model\Reason\ResourceModel\CollectionFactory as ReasonCollectionFactory;
use Pratech\Return\Model\RejectReason\RejectReasonFactory;
use Pratech\Return\Model\Request\Request;
use Pratech\Return\Model\Request\RequestFactory;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory as ReturnCollectionFactory;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;
use Pratech\Return\Model\TrackUpdates\ReturnTrackUpdatesFactory;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Order Return Helper Class to power order api.
 */
class OrderReturn
{
    /**
     * Config Path for Kapture Url
     */
    const CONFIG_PATH_KAPTURE_URL = 'return/return/kapture_url';

    /**
     * Config Path for Kapture Key
     */
    const CONFIG_PATH_KAPTURE_KEY = 'return/return/kapture_key';

    /**
     * Config Path for Return Period
     */
    const CONFIG_PATH_RETURN_PERIOD = 'return/return/return_period_days';

    /**
     * Order Return Helper Constructor
     *
     * @param Logger $logger
     * @param TimezoneInterface $timezone
     * @param ShippingConfig $shippingConfig
     * @param ManagerInterface $eventManager
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param RequestRepositoryInterface $requestRepository
     * @param ReasonCollectionFactory $reasonCollectionFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param HistoryCollectionFactory $historyCollectionFactory
     * @param ReturnTrackUpdatesFactory $returnTrackUpdatesFactory
     * @param ShipmentStatusCollectionFactory $shipmentStatusCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ReasonFactory $reasonFactory
     * @param RejectReasonFactory $rejectReasonFactory
     * @param CreateReturnProcessorInterface $createReturnProcessor
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param ReturnRefundLogger $returnRefundLogger
     * @param BalanceFactory $balanceFactory
     * @param SqsEvent $sqsEvent
     * @param CustomerRepositoryInterface $customerRepository
     * @param Curl $curl
     * @param EncryptorInterface $encryptor
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private Logger                          $logger,
        private TimezoneInterface               $timezone,
        private ShippingConfig                  $shippingConfig,
        private ManagerInterface                $eventManager,
        private OrderItemRepositoryInterface    $orderItemRepository,
        private StatusCollectionFactory         $statusCollectionFactory,
        private RequestRepositoryInterface      $requestRepository,
        private ReasonCollectionFactory         $reasonCollectionFactory,
        private ShipmentRepositoryInterface     $shipmentRepository,
        private HistoryCollectionFactory        $historyCollectionFactory,
        private ReturnTrackUpdatesFactory       $returnTrackUpdatesFactory,
        private ShipmentStatusCollectionFactory $shipmentStatusCollectionFactory,
        private ScopeConfigInterface            $scopeConfig,
        private ReasonFactory                   $reasonFactory,
        private RejectReasonFactory             $rejectReasonFactory,
        private CreateReturnProcessorInterface  $createReturnProcessor,
        private OrderRepositoryInterface        $orderRepository,
        private StoreManagerInterface           $storeManager,
        private ReturnRefundLogger              $returnRefundLogger,
        private BalanceFactory                  $balanceFactory,
        private SqsEvent                        $sqsEvent,
        private CustomerRepositoryInterface     $customerRepository,
        private Curl                            $curl,
        private EncryptorInterface              $encryptor,
        private ProductRepositoryInterface      $productRepository,
        private ResourceConnection              $resourceConnection
    )
    {
    }

    /**
     * Get Order Return Reasons list
     *
     * @return array
     */
    public function getReturnReasons(): array
    {
        $result = [];
        try {
            $reasonsCollection = $this->reasonCollectionFactory->create()
                ->addFieldToFilter('is_deleted', 0)
                ->addFieldToFilter('status', 1)
                ->setOrder('position', 'ASC');
            foreach ($reasonsCollection as $reason) {
                $result[] = [
                    'reason_id' => $reason->getId(),
                    'title' => $reason->getTitle(),
                    'position' => $reason->getPosition()
                ];
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }
        return $result;
    }

    /**
     * Get Order return status list
     *
     * @return array
     */
    public function getReturnStatus(): array
    {
        $result = [];
        try {
            $statusCollection = $this->statusCollectionFactory->create()
                ->addFieldToFilter('is_enabled', 1)
                ->addFieldToFilter('is_deleted', 0)
                ->setOrder('priority', 'ASC');

            foreach ($statusCollection as $status) {
                $result[] = [
                    'status_id' => $status->getStatusId(),
                    'title' => $status->getTitle(),
                    'is_initial' => $status->getIsInitial()
                ];
            }
        } catch (Exception $exception) {
            $this->logger->info(__METHOD__ . $exception->getMessage());
        }
        return $result;
    }

    /**
     * Get Status ID by status code
     *
     * @param string $statusCode
     * @return int
     */
    public function getStatusId(string $statusCode): int
    {
        $status = $this->shipmentStatusCollectionFactory->create()
            ->addFieldToFilter('clickpost_status', $statusCode);

        if ($status->getSize()) {
            return $status->getFirstItem()->getStatusId();
        }

        return 0;
    }

    /**
     * Get Reject Reason Title By Id
     *
     * @param int $reasonId
     * @return string
     */
    public function getRejectReasonTitleById(int $reasonId): string
    {
        $reason = $this->rejectReasonFactory->create()->load($reasonId);

        if ($reason) {
            return $reason->getTitle();
        }
        return "";
    }

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
        int                      $shipmentId,
        array                    $items,
        string                   $comment = null,
        ?PaymentDetailsInterface $paymentDetails = null
    ): array
    {
        $shipment = $this->shipmentRepository->get($shipmentId);
        if (!$shipment || $shipment->getShipmentStatus() != 4) {
            throw new Exception(__('Return request can only be created after the order is delivered.'));
        }
        $isReturnEligible = $this->isReturnEligible($shipment);
        if (!$isReturnEligible) {
            throw new Exception(__('Return request can\'t process.'));
        }

        try {
            $orderId = $shipment->getOrderId();
            $order = $shipment->getOrder();
            if ($returnOrder = $this->createReturnProcessor->process($orderId, true)) {
                $refundStatusId = $this->getInitialRefundStatusId();
                $request = $this->requestRepository->getEmptyRequestModel();
                $request->setOrderId($orderId)
                    ->setShipmentId($shipmentId)
                    ->setComment($comment)
                    ->setStatus($this->getInitialStatusId())
                    ->setRefundStatus($refundStatusId)
                    ->setCustomerId($shipment->getCustomerId())
                    ->setCustomerName(
                        $order->getBillingAddress()->getFirstname() . ' '
                        . $order->getBillingAddress()->getLastname()
                    );

                $processedItems = $this->processItems($order, $returnOrder->getItems(), $items);

                if (empty($processedItems)) {
                    throw new Exception(__('No valid items found for return.'));
                }

                $request->setRequestItems($processedItems);
                $this->requestRepository->save($request);

                $this->savePaymentDetails($request->getRequestId(), $paymentDetails);

                $order->setReturnRequests(
                    $order->getReturnRequests() ? $order->getReturnRequests() . ","
                        . $request->getRequestId() : $request->getRequestId()
                );
                $order->save();

                $this->eventManager->dispatch('return_request_created', ['request' => $request]);

                $this->createKaptureTicket($shipment->getCustomerId(), 'Return Ticket', []);

                return [
                    'request_id' => $request->getRequestId(),
                    'shipmentId' => $shipmentId,
                    'comment' => $request->getComment(),
                    'status' => $request->getStatus()
                ];
            } else {
                throw new Exception(__('Return request can\'t process.'));
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . " Error: " . $exception->getMessage());
            throw new Exception(__('Error processing return request.'));
        }
    }

    /**
     * Check if all items in the shipment are returned or return period has expired.
     *
     * @param Shipment $shipment
     * @return bool
     */
    public function isReturnEligible(Shipment $shipment): bool
    {
        $returnPeriodDays = (int)$this->getConfig(
            self::CONFIG_PATH_RETURN_PERIOD
        );

        if ($shipment->getShipmentStatus() != 4) {
            return false;
        }

        $shipmentUpdatedAt = $this->getTimeBasedOnTimezone($shipment->getUpdatedAt());
        $currentDate = $this->timezone->date()->format('j M, o');
        $daysSinceDelivery = (strtotime($currentDate) - strtotime($shipmentUpdatedAt)) / 86400;

        $allItemsReturned = true;
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            if ($orderItem->getQtyOrdered() > $orderItem->getQtyReturned()) {
                $allItemsReturned = false;
                break;
            }
        }

        return !($allItemsReturned || $daysSinceDelivery > $returnPeriodDays);
    }

    /**
     * Get System Config
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
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
            $locale = $this->getConfig(
                'general/locale/timezone'
            );
            return $this->timezone->date(new DateTime($date), $locale)->format('j M, o');
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Initial Status ID
     *
     * @return int|null
     */
    public function getInitialRefundStatusId(): ?int
    {
        $status = $this->shipmentStatusCollectionFactory->create()
            ->addFieldToFilter('status_code', 'refund_pending');
        if ($status->getSize()) {
            return $status->getFirstItem()->getStatusId();
        }
        return null;
    }

    /**
     * Get Initial Status ID
     *
     * @return int|null
     */
    public function getInitialStatusId(): ?int
    {
        $status = $this->shipmentStatusCollectionFactory->create()
            ->addFieldToFilter('status_code', 'return_pending');
        if ($status->getSize()) {
            return $status->getFirstItem()->getStatusId();
        }
        return null;
    }

    /**
     * Process Return Items
     *
     * @param Order $order
     * @param ReturnOrderItemInterface[] $orderItems
     * @param ReturnItemInterface[] $items
     * @return array
     */
    public function processItems(Order $order, array $orderItems, array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (!empty($item[RequestItemInterface::QTY])
                && !empty($item[ReturnItemInterface::REASON_ID])
                && $orderItem = $this->getOrderItemByOrderItemId(
                    $orderItems,
                    (int)$item[ReturnItemInterface::ORDER_ITEM_ID]
                )
            ) {
                if ($orderItem->getAvailableQty() > 0.0001
                    && $orderItem->getAvailableQty() >= (double)$item[RequestItemInterface::QTY]
                ) {
                    $itemStatus = (!empty($item[RequestItemInterface::ITEM_STATUS])
                        && $item[RequestItemInterface::ITEM_STATUS] == 'true') ? ItemStatus::AUTHORIZED : 0;

                    $refundedAmount = $this->getRefundedAmountByItemId(
                        $order,
                        $orderItem->getItem()->getItemId(),
                        $item[ReturnItemInterface::QTY]
                    );

                    $requestItem = $this->requestRepository->getEmptyRequestItemModel();
                    $requestItem->setItemStatus($itemStatus)
                        ->setOrderItemId($orderItem->getItem()->getItemId())
                        ->setReasonId($item[ReturnItemInterface::REASON_ID])
                        ->setRequestQty($item[ReturnItemInterface::QTY])
                        ->setImages(json_encode($item[ReturnItemInterface::MEDIADATA]))
                        ->setQty($item[ReturnItemInterface::QTY])
                        ->setRefundedAmount($refundedAmount)
                        ->setComment($item[ReturnItemInterface::COMMENT]);

                    $result[] = $requestItem;
                    $this->updateQtyReturnedInOrderItem(
                        $order,
                        $item[ReturnItemInterface::ORDER_ITEM_ID],
                        $item[ReturnItemInterface::QTY]
                    );
                }
            }
        }
        return $result;
    }

    /**
     * Get Order Item by Order Item ID
     *
     * @param ReturnOrderItemInterface[] $orderItems
     * @param int $orderItemId
     * @return ReturnOrderItemInterface|bool
     */
    public function getOrderItemByOrderItemId(array $orderItems, int $orderItemId)
    {
        foreach ($orderItems as $orderItem) {
            if ((int)$orderItem->getItem()->getItemId() === $orderItemId) {
                return $orderItem;
            }
        }
        return false;
    }

    /**
     * Get the refunded amount for a specific order item ID.
     *
     * @param Order $order
     * @param int $orderItemId
     * @param int $qtyToRefund
     * @return float
     */
    public function getRefundedAmountByItemId(Order $order, int $orderItemId, int $qtyToRefund): float
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
     * Update Qty Returned in Sales Order Item
     *
     * @param Order $order
     * @param int $orderItemId
     * @param int $returnedQty
     * @return void
     */
    public function updateQtyReturnedInOrderItem(Order $order, int $orderItemId, int $returnedQty): void
    {
        try {
            $orderItems = $order->getAllItems();
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getItemId() == $orderItemId) {
                    $existingQtyReturned = (int)$orderItem->getQtyReturned();

                    $orderItem->setQtyReturned($existingQtyReturned + $returnedQty);
                    break;
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to update qty returned. Error: " . $e->getMessage());
        }
    }

    /**
     * Save Payment Details in DB
     *
     * @param int $requestId
     * @param PaymentDetailsInterface|null $paymentDetails
     * @return void
     */
    public function savePaymentDetails(int $requestId, ?PaymentDetailsInterface $paymentDetails): void
    {
        if (!$paymentDetails) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('sales_order_return_payment_details');

        // Determine Payment Type
        $paymentType = null;
        if (!empty($paymentDetails->getUpiId())) {
            $paymentType = 'upi';
        } elseif ($paymentDetails->getBankAccountDetails()
            && !empty($paymentDetails->getBankAccountDetails()->getAccountNumber())) {
            $paymentType = 'bank_transfer';
        }

        // If no valid payment type, return without inserting
        if (!$paymentType) {
            return;
        }

        // Prepare data for insertion
        $data = [
            'request_id' => $requestId,
            'payment_type' => $paymentType,
            'upi_id' => $paymentType === 'upi' ? $paymentDetails->getUpiId() : null,
            'account_number' => $paymentType === 'bank_transfer'
                ? $paymentDetails->getBankAccountDetails()->getAccountNumber()
                : null,
            'ifsc_code' => $paymentType === 'bank_transfer'
                ? $paymentDetails->getBankAccountDetails()->getIfscCode()
                : null,
            'account_holder_name' => $paymentType === 'bank_transfer'
                ? $paymentDetails->getBankAccountDetails()->getAccountHolderName()
                : null
        ];

        $connection->insert($tableName, $data);
    }

    /**
     * Create a ticket on Kapture
     *
     * @param int $customerId
     * @param string $title
     * @param array $ticketDetails
     * @param string|null $dueDate
     * @return array
     */
    public function createKaptureTicket(int $customerId, string $title, array $ticketDetails, string $dueDate = null)
    {
        try {
            $kaptureUrl = $this->getConfig(self::CONFIG_PATH_KAPTURE_URL);
            $kaptureKey = $this->encryptor->decrypt(
                $this->getConfig(self::CONFIG_PATH_KAPTURE_KEY)
            );

            if (!$kaptureUrl || !$kaptureKey) {
                $this->logger->error("Kapture API credentials are not configured.");
            }
            $customerDetails = $this->getCustomerDetailsById($customerId);

            $payload = [
                [
                    "title" => $title,
                    "ticket_details" => $ticketDetails,
                    "due_date" => $dueDate ?? "",
                    "customer_name" => $customerDetails['name'],
                    "phone" => $customerDetails['phone'],
                    "email_id" => $customerDetails['email']
                ]
            ];

            $jsonData = json_encode($payload);

            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer " . $kaptureKey
            ]);
            $this->curl->post($kaptureUrl, $jsonData);

            $response = $this->curl->getBody();

            $this->logger->info('Kapture API Response: ' . $response);

            return [];
        } catch (Exception $e) {
            $this->logger->info('Kapture API Error: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Get Customer Details By ID
     *
     * @param int $customerId
     * @return array
     */
    public function getCustomerDetailsById(int $customerId): array
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return [
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getCustomAttribute('mobile_number')
                    ? $customer->getCustomAttribute('mobile_number')->getValue()
                    : 'N/A'
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update Return Status By Tracking Number
     *
     * @param string $trackNumber
     * @param string $returnStatus
     * @return array
     */
    public function updateReturnStatus(string $trackNumber, string $returnStatus): array
    {
        try {
            $tracking = $this->requestRepository->getTrackingByTrackingNumber($trackNumber);

            if ($tracking && $tracking->getId()) {
                $request = $this->requestRepository->getById($tracking->getRequestId());
                $status = $this->getStatusId($returnStatus);

                if ($status) {
                    $statusBefore = $request->getStatus();
                    $request->setStatus($status);
                    $result = $this->requestRepository->save($request);
                    $this->eventManager->dispatch(
                        'return_status_changed_from_clickpost',
                        [
                            'from' => $statusBefore,
                            'to' => $status,
                            'request' => $request
                        ]
                    );
                    return [
                        "request_id" => $result->getRequestId(),
                        "return_status" => $result->getStatus(),
                        "updated_at" => $result->getModifiedAt()
                    ];
                }
            }
        } catch (Exception $exception) {
            $this->logger->info(
                sprintf(
                    "Unable to update return status for Tracking Number: %s. Error: %s",
                    $trackNumber,
                    $exception->getMessage()
                )
            );
        }

        return [];
    }

    /**
     * Update Return Status By Tracking Number
     *
     * @param int $requestId
     * @param string $refundStatus
     * @return array
     */
    public function updateRefundStatus(int $requestId, string $refundStatus): array
    {
        try {
            $request = $this->requestRepository->getById($requestId);

            if ($request && $request->getId()) {
                $status = $this->getStatusId($refundStatus);

                if ($status) {
                    $request->setRefundStatus($status);
                    $result = $this->requestRepository->save($request);
                    return [
                        "request_id" => $result->getRequestId(),
                        "refund_status" => $result->getRefundStatus(),
                        "updated_at" => $result->getModifiedAt()
                    ];
                }
            }
        } catch (Exception $exception) {
            $this->logger->info(
                sprintf(
                    "Unable to update return refund status for request id: %s. Error: %s",
                    $requestId,
                    $exception->getMessage()
                )
            );
        }

        return [];
    }

    /**
     * Get Order Returns Data
     *
     * @param Order $order
     * @param int|null $shipmentId
     * @return array
     */
    public function getOrderReturns(Order $order, int $shipmentId = null): array
    {
        $returnsData = [];
        try {
            $returnRequestIds = $order->getReturnRequests();

            if (!empty($returnRequestIds) && is_string($returnRequestIds)) {
                $returnIdsArray = array_map('trim', explode(",", $returnRequestIds));

                foreach ($returnIdsArray as $returnId) {
                    $returnData = $this->getReturnRequest($returnId, $shipmentId);
                    if (!empty($returnData)) {
                        $returnsData[] = $returnData;
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->info(
                sprintf(
                    "Error fetching return requests for Order ID %d: %s",
                    $order->getId(),
                    $exception->getMessage()
                )
            );
        }

        return $returnsData;
    }

    /**
     * Get Return Request
     *
     * @param int $requestId
     * @param int|null $shipmentId
     * @return array
     */
    public function getReturnRequest(int $requestId, int $shipmentId = null): array
    {
        try {
            $request = $this->requestRepository->getById($requestId);
            $cancelStatusId = $this->getStatusId('return_canceled');
            if (($shipmentId && $request->getShipmentId() != $shipmentId) || $request->getStatus() == $cancelStatusId) {
                return [];
            }

            $statusCode = $this->getStatusCodeById($request->getStatus());
            $refundStatusCode = $this->getStatusCodeById($request->getRefundStatus());

            $statusLabel = $this->getStatusLabel($statusCode);
            $refundStatusLabel = $this->getStatusLabel($refundStatusCode);

            $status = !empty($statusLabel) ? $statusLabel : $this->getStatusTitleById($request->getStatus());
            $refundStatus = !empty($refundStatusLabel) ? $refundStatusLabel : $this->getStatusTitleById($request->getRefundStatus());

            $paymentDetails = $this->getPaymentDetailsByRequestId($request->getRequestId());

            $refundMode = 'Back To Source payment mode';
            if ($paymentDetails && !empty($paymentDetails['payment_type'])) {

                $types = [
                    'upi' => 'UPI',
                    'bank_transfer' => 'Bank Transfer'
                ];
                $refundMode = $types[$paymentDetails['payment_type']];
            }
            return [
                'request_id' => $request->getRequestId(),
                'return_increment_id' => 1000000000 + (int)$request->getRequestId(),
                'created_at' => $this->getTimeBasedOnTimezone($request->getCreatedAt()),
                'comment' => $request->getComment(),
                'status' => $status,
                'status_code' => $statusCode,
                'refund_status' => $refundStatus,
                'refund_status_code' => $refundStatusCode,
                "order_id" => $request->getOrderId(),
                "refunded_amount" => number_format($request->getRefundedAmount(), 2),
                "refunded_hcash" => number_format($request->getRefundedStoreCredit(), 2),
                "return_items" => $this->getReturnRequestItems($request),
                "tracking" => $this->getTrackingInfo($request->getRequestId()),
                "reject_reason" => $request->getRejectReasonId() ?
                    $this->getRejectReasonTitleById($request->getRejectReasonId())
                    : null,
                'refund_mode' => $refundMode,
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->info("Return request not found: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Get status label mapping
     *
     * @param string $statusCode
     * @return string
     */
    private function getStatusLabel(string $statusCode): string
    {
        $statusMap = [
            "return_pending" => "Return request submitted",
            "return_approved" => "Return request approved",
            "return_initiated" => "Return request processed",
            "return_rejected" => "Return request rejected",
            "return_canceled" => "Return request cancelled",
            "pickup_pending" => "Return pickup pending",
            "out_for_pickup" => "Return out for pickup",
            "pickup_failed" => "Return pickup failed",
            "picked_up" => "Return picked up",
            "intransit" => "Return intransit",
            "out_for_delivery" => "Return intransit",
            "delivered" => "Return received & under review",
            "refund_completed" => "Refund processed",
            "refund_initiated" => "Refund initiated",
        ];

        return $statusMap[$statusCode] ?? "";
    }

    /**
     * Get Status Title using Status Id
     *
     * @param int $statusId
     * @return string
     */
    public function getStatusTitleById(int $statusId): string
    {
        try {
            $status = $this->shipmentStatusCollectionFactory->create()
                ->addFieldToFilter('status_id', $statusId);

            if ($status->getSize()) {
                return $status->getFirstItem()->getStatus();
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Return status not found: " . $e->getMessage());
        }
        return " ";
    }

    /**
     * Get Status code by status id
     *
     * @param int $statusId
     * @return string
     */
    public function getStatusCodeById(int $statusId): string
    {
        $status = $this->shipmentStatusCollectionFactory->create()
            ->addFieldToFilter('status_id', $statusId);

        if ($status->getSize()) {
            return $status->getFirstItem()->getClickpostStatus();
        }

        return "";
    }

    /**
     * Get Return Request Items
     *
     * @param Request $request
     * @return array
     */
    public function getReturnRequestItems(Request $request): array
    {
        $itemsData = [];
        $mediaBaseUrl = "";
        try {
            $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Error in getting media base url: " . $e->getMessage());
        }

        foreach ($request->getRequestItems() as $item) {
            $orderItemId = $item->getOrderItemId();

            try {
                $orderItem = $this->orderItemRepository->get($orderItemId);
                $product = $this->productRepository->get($orderItem->getSku());
                $productSlug = $product->getUrlKey();
                $productImage = $product->getImage() ? $mediaBaseUrl . 'catalog/product' . $product->getImage() : "";
                $itemsData[] = [
                    'request_item_id' => $item->getRequestItemId(),
                    'order_item_id' => $orderItemId,
                    'slug' => $productSlug,
                    'name' => $orderItem->getName(),
                    'price' => (float)$orderItem->getPrice(),
                    'product_image' => $productImage,
                    'return_qty' => $item->getQty(),
                    'status' => $item->getItemStatus(),
                    'refunded_amount' => $item->getRefundedAmount(),
                    'reason' => $this->getReasonTitleById($item->getReasonId()),
                    'images' => $item->getImages() ? json_decode($item->getImages(), true) : []
                ];
            } catch (NoSuchEntityException $e) {
                $this->logger->error("Error in getReturnRequestItems: " . $e->getMessage());
            }
        }
        return $itemsData;
    }

    /**
     * Get Reason Title By ID
     *
     * @param int $reasonId
     * @return string
     */
    public function getReasonTitleById(int $reasonId): string
    {
        $reason = $this->reasonFactory->create()->load($reasonId);

        if ($reason) {
            return $reason->getTitle();
        }
        return "";
    }

    /**
     * Get Tracking Info
     *
     * @param int $requestId
     * @return array
     */
    public function getTrackingInfo(int $requestId): array
    {
        $result = [];
        $historyCollection = $this->historyCollectionFactory->create()
            ->addFieldToFilter('request_id', $requestId)
            ->addFieldToFilter('event_type', ['in' => [0, 6]])
            ->addFieldToFilter('event_type', ['in' => [0, 6]])
            ->addFieldToSelect(['event_date', 'request_status_id'])
            ->addOrder('event_date', 'ASC');

        foreach ($historyCollection as $record) {
            $result[] = [
                'status_code' => $this->getStatusCodeById($record->getRequestStatusId()),
                'status' => $this->getStatusTitleById($record->getRequestStatusId()),
                'created_at' => $this->getTimeBasedOnTimezone($record->getEventDate())
            ];
        }
        return $result;
    }

    /**
     * Get Initial Status Id
     *
     * @return int|null
     */
    public function getRejectStatusId(): ?int
    {
        $status = $this->shipmentStatusCollectionFactory->create()
            ->addFieldToFilter('status_code', 'return_rejected');
        if ($status->getSize()) {
            return $status->getFirstItem()->getStatusId();
        }
        return null;
    }

    /**
     * Get Carriers
     *
     * @param boolean $toArray
     * @return array
     */
    public function getCarriers(bool $toArray = false): array
    {
        $carriers = [];
        $carrierInstances = $this->shippingConfig->getAllCarriers(1);
        if ($toArray) {
            $carriers['custom'] = 'Custom Value';
        } else {
            $carriers[] = [
                'code' => 'custom',
                'label' => 'Custom Value'
            ];
        }
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                if ($toArray) {
                    $carriers[$code] = $carrier->getConfigData('title');
                } else {
                    $carriers[] = [
                        'code' => $code,
                        'label' => $carrier->getConfigData('title')
                    ];
                }
            }
        }
        return $carriers;
    }

    /**
     * Set Return Track Details.
     *
     * @param string $trackNumber
     * @param string $location
     * @param string $remark
     * @param int $clickPostStatus
     * @return array|string[]
     */
    public function setReturnTrackDetails(
        string $trackNumber,
        string $location,
        string $remark,
        int    $clickPostStatus
    ): array
    {
        $trackDetailItems = [
            "return_id" => "",
            "location" => "",
            "remark" => "",
            "status" => ""
        ];

        try {
            $tracking = $this->requestRepository->getTrackingByTrackingNumber($trackNumber);
            if ($tracking && $tracking->getId()) {
                $trackUpdates = $this->returnTrackUpdatesFactory->create();
                $trackUpdates->setParentId($tracking->getRequestId())
                    ->setTrackNumber($trackNumber)
                    ->setLocation($location)
                    ->setRemark($remark);

                $statusCollection = $this->shipmentStatusCollectionFactory->create()
                    ->addFieldToFilter('clickpost_status_code', $clickPostStatus);
                if ($statusCollection->getSize() > 0) {
                    $status = $statusCollection->getFirstItem();
                    $trackUpdates->setStatusId($status->getStatusId());
                }
                $trackUpdates->save();

                $trackDetailItems = [
                    "return_id" => $trackUpdates->getParentId(),
                    "location" => $trackUpdates->getLocation(),
                    "remark" => $trackUpdates->getRemark(),
                    "status" => $trackUpdates->getStatusId(),
                ];
            }
        } catch (Exception $exception) {
            $this->logger->info(
                "Tracking Number Not Exists: " . $trackNumber .
                $exception->getMessage() . __METHOD__
            );
        }
        return $trackDetailItems;
    }

    /**
     * Cancel Order Return Request
     *
     * @param int $requestId
     * @return array
     * @throws Exception
     */
    public function cancelReturnRequest(int $requestId): array
    {
        try {
            $request = $this->requestRepository->getById($requestId);

            if (!$request) {
                throw new Exception(__('Return request not found.'));
            }
            $statusCode = $this->getStatusCodeById($request->getStatus());
            if ($statusCode != 'return_pending') {
                throw new Exception(__('You can\'t cancel the return request. Please contact the support team.'));
            }

            $cancelStatusId = $this->getStatusId('return_canceled');
            $request->setStatus($cancelStatusId);
            $request->save();

            $orderId = $request->getOrderId();
            $order = $this->orderRepository->get($orderId);

            foreach ($request->getRequestItems() as $item) {
                $orderItemId = $item->getOrderItemId();
                $returnedQty = (int)$item->getRequestQty();

                foreach ($order->getAllItems() as $orderItem) {
                    if ($orderItem->getItemId() == $orderItemId) {
                        $existingQtyReturned = (int)$orderItem->getQtyReturned();
                        $newQty = max(0, $existingQtyReturned - $returnedQty);
                        $orderItem->setQtyReturned($newQty);
                        break;
                    }
                }
            }

            $this->orderRepository->save($order);

            $this->eventManager->dispatch('return_request_canceled', ['request' => $request]);

            return [
                'request_id' => $request->getRequestId(),
                'message' => __('Your return request has been successfully canceled.'),
            ];
        } catch (Exception $exception) {
            $this->logger->info(__METHOD__ . ' Error: ' . $exception->getMessage());
            throw new Exception(__('Unable to cancel return request. Please try again later.'));
        }
    }

    /**
     * Trigger Refund for Return Request.
     *
     * @param RequestInterface $returnRequest
     * @param string $eventName
     * @return float
     */
    public function triggerRefundForReturn(RequestInterface $returnRequest, string $eventName): float
    {
        try {
            $refundStatus = $returnRequest->getRefundStatus();

            if ($refundStatus != $this->getInitialRefundStatusId()) {
                return false;
            }
            $order = $this->orderRepository->get($returnRequest->getOrderId());

            if ($order->getPayment()->getMethod() === 'cashondelivery') {
                return false;
            }
            $returnRequestItems = $this->getReturnRequestItems($returnRequest);
            $refundAmount = $this->updateRefundAndRevertStoreCredit($order, $returnRequestItems);

            // Revert H Cash for return
            $this->revertStoreCreditForReturnRequest($order, $returnRequest->getRefundedStoreCredit());

            $refundPayload = $this->getRefundDataForReturnRequest($returnRequest, $order, $refundAmount);

            $this->returnRefundLogger->info(
                "Return Refund for Order ID: " . $order->getIncrementId()
                . " | Return Request ID: " . $returnRequest->getRequestId(),
                [
                    'refund_amount' => $refundAmount,
                    'refund_payload' => $refundPayload
                ]
            );

            $this->sqsEvent->initiateRefundOnSqs($refundPayload, $eventName);
            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }

        return false;
    }

    /**
     * Calculate Refund Amount for Return Request.
     *
     * @param Order $order
     * @param array $returnRequestItems
     * @return float|int
     */
    private function updateRefundAndRevertStoreCredit(Order $order, array $returnRequestItems): float|int
    {
        try {
            $refundTotal = 0;
            $orderItems = $order->getItems();

            foreach ($returnRequestItems as $returnItem) {
                if ($returnItem['status'] != 1) {
                    continue;
                }
                foreach ($orderItems as $orderItem) {
                    if ($orderItem->getItemId() == $returnItem['order_item_id']) {
                        $refundTotal += $returnItem['refunded_amount'];

                        $orderItem->setQtyRefunded($orderItem->getQtyRefunded() + $returnItem['return_qty'])
                            ->setAmountRefunded(
                                $orderItem->getAmountRefunded() + $returnItem['refunded_amount']
                            )
                            ->setBaseAmountRefunded(
                                $orderItem->getBaseAmountRefunded() + $returnItem['refunded_amount']
                            );
                    }
                }
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
     * Revert store credit for the return request.
     *
     * @param Order $order
     * @param float $storeCreditPoints
     * @return void
     */
    public function revertStoreCreditForReturnRequest(Order $order, float $storeCreditPoints): void
    {
        try {
            if ($storeCreditPoints > 0) {
                $conversionRate = $this->getConfig(
                    'store_credit/store_credit/conversion_rate'
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
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Error reverting store credit for return: " . $e->getMessage() . __METHOD__);
        }
    }

    /**
     * Prepare refund data for return request items.
     *
     * @param RequestInterface $returnRequest
     * @param Order $order
     * @param float $refundAmount
     * @return array
     */
    private function getRefundDataForReturnRequest(
        RequestInterface $returnRequest,
        Order            $order,
        float            $refundAmount
    ): array
    {
        $paymentInformation = $this->getPaymentDetails($order);
        $paymentInformation['refund_amount'] = $refundAmount;

        return [
            'order_entity_id' => $order->getEntityId(),
            'order_increment_id' => $order->getIncrementId(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_lastname' => $order->getCustomerLastname(),
            'contact_information' => [
                'mobile_number' => $order->getShippingAddress()->getTelephone(),
                'email_address' => $order->getCustomerEmail()
            ],
            'payment_information' => $paymentInformation,
            'order_information' => [
                'placed_on' => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                'increment_id' => $order->getIncrementId()
            ],
            'return_request' => [
                'request_id' => $returnRequest->getRequestId(),
                'created_on' => $this->getTimeBasedOnTimezone($returnRequest->getCreatedAt()),
                'items' => $this->getReturnRequestItemsData($returnRequest)
            ]
        ];
    }

    /**
     * Get Payment Details
     *
     * @param Order $order
     * @return array
     */
    public function getPaymentDetails(Order $order): array
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
     * Get return request items data.
     *
     * @param RequestInterface $returnRequest
     * @return array
     */
    private function getReturnRequestItemsData(RequestInterface $returnRequest): array
    {
        $itemsData = [];
        foreach ($returnRequest->getRequestItems() as $item) {
            $itemsData[] = [
                'request_item_id' => $item->getRequestItemId(),
                'order_item_id' => $item->getOrderItemId(),
                'return_qty' => $item->getQty(),
                'reason' => $this->getReasonTitleById($item->getReasonId()),
                'images' => $item->getImages() ? json_decode($item->getImages(), true) : []
            ];
        }
        return $itemsData;
    }

    /**
     * Calculate Refunded Amount & Store Credit
     *
     * @param RequestInterface $returnRequest
     * @return array
     */
    public function calculateRefundedAmountAndStoreCredit(RequestInterface $returnRequest): array
    {
        try {
            $result = [
                'refunded_amount' => 0,
                'refunded_store_credit' => 0
            ];
            $refundTotal = 0;
            $order = $this->orderRepository->get($returnRequest->getOrderId());
            $orderItems = $order->getItems();
            $orderTotalWithoutShipping = $order->getGrandTotal() - $order->getDeliveryCharges();
            $returnRequestItems = $this->getReturnRequestItems($returnRequest);
            foreach ($returnRequestItems as $returnItem) {
                if ($returnItem['status'] != 1) {
                    continue;
                }
                foreach ($orderItems as $orderItem) {

                    if ($orderItem->getItemId() == $returnItem['order_item_id']) {

                        $refundTotal += ($orderItem->getRowTotal() - $orderItem->getDiscountAmount())
                            * $returnItem['return_qty'] / $orderItem->getQtyOrdered();
                    }
                }
            }

            $result['refunded_store_credit'] = ($refundTotal / $orderTotalWithoutShipping) * $order->getCustomerBalanceAmount();
            $result['refunded_amount'] = $refundTotal;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Payment Details by Request ID
     *
     * @param int $requestId
     * @return array|null
     */
    public function getPaymentDetailsByRequestId(int $requestId): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_order_return_payment_details');

        $select = $connection->select()
            ->from($tableName)
            ->where('request_id = ?', $requestId)
            ->limit(1);

        $result = $connection->fetchRow($select);

        return $result ?: null;
    }
}
