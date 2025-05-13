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

namespace Pratech\Return\Observer\Rma;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Return\Api\Data\ReasonInterface;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\History\CreateEvent;
use Pratech\Return\Model\OptionSource\EventInitiator;
use Pratech\Return\Model\OptionSource\EventType;
use Pratech\Return\Model\OptionSource\ItemStatus;
use Pratech\Return\Model\OptionSource\Manager;
use Pratech\Return\Model\Request\Request;
use Pratech\Return\Model\Request\ResourceModel\Request as RequestResource;
use Pratech\SqsIntegration\Model\SqsEvent;

class History implements ObserverInterface
{
    /**
     * History Observer Constructor
     *
     * @param CreateEvent $createEvent
     * @param ReasonInterface $reasonInterface
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ItemStatus $itemStatus
     * @param Manager $managers
     * @param OrderFactory $orderFactory
     * @param StoreManagerInterface $storeManager
     * @param OrderReturnHelper $orderReturnHelper
     * @param SqsEvent $sqsEvent
     * @param RequestResource $requestResource
     * @param Logger $logger
     */
    public function __construct(
        private CreateEvent                  $createEvent,
        private ReasonInterface              $reasonInterface,
        private OrderItemRepositoryInterface $orderItemRepository,
        private ItemStatus                   $itemStatus,
        private Manager                      $managers,
        private OrderFactory                 $orderFactory,
        private StoreManagerInterface        $storeManager,
        private OrderReturnHelper            $orderReturnHelper,
        private SqsEvent                     $sqsEvent,
        private RequestResource              $requestResource,
        private Logger                       $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /**
         * @var RequestInterface $request
         */
        $request = $observer->getData('request');
        switch ($observer->getEvent()->getName()) {
            case 'return_request_created':
                $this->createEvent->execute(
                    EventType::RMA_CREATED,
                    $request,
                    EventInitiator::CUSTOMER
                );
                $this->sendReturnSQSEvent($request, 'return_created');
                break;
            case 'return_request_created_by_admin':
                $this->createEvent->execute(EventType::RMA_CREATED, $request, EventInitiator::MANAGER);
                break;
            case 'return_request_canceled':
                $this->createEvent->execute(
                    EventType::CUSTOMER_CLOSED_RMA,
                    $request,
                    EventInitiator::CUSTOMER
                );
                break;
            case 'return_status_changed_from_clickpost':
                $this->createEvent->execute(
                    EventType::STATUS_CHANGED_FROM_CLICKPOST,
                    $request,
                    EventInitiator::SYSTEM,
                    [
                        $this->orderReturnHelper->getStatusTitleById($observer->getData('from')),
                        $this->orderReturnHelper->getStatusTitleById($observer->getData('to')),
                    ]
                );
                break;
            case 'return_request_saved':
                $before = $after = [];
                $itemStatuses = $this->itemStatus->toArray();
                if ($request->getStatus() != $request->getOrigData(RequestInterface::STATUS)) {
                    $before['status'] = $this->orderReturnHelper->getStatusTitleById(
                        $request->getOrigData(RequestInterface::STATUS)
                    );
                    $after['status'] = $this->orderReturnHelper->getStatusTitleById($request->getStatus());
                }

                if ($request->getManagerId() != $request->getOrigData(RequestInterface::MANAGER_ID)) {
                    $before['manager'] = $this->managers->toArray()
                    [$request->getOrigData(RequestInterface::MANAGER_ID)];
                    $after['manager'] = $this->managers->toArray()[$request->getManagerId()];
                }

                if ($request->getNote() != $request->getOrigData(RequestInterface::NOTE)) {
                    $before['note'] = $request->getOrigData(RequestInterface::NOTE) ?: '';
                    $after['note'] = $request->getNote();
                }

                $splitItems = $items = [];
                foreach ($request->getRequestItems() as $item) {
                    if ($item->getOrigData(RequestItemInterface::REQUEST_ITEM_ID)) {
                        $changes = [];

                        if ($item->getItemStatus() != $item->getOrigData(RequestItemInterface::ITEM_STATUS)) {
                            $changes['before']['state'] = !empty(
                                $itemStatuses[$item->getOrigData(RequestItemInterface::ITEM_STATUS)]
                            ) ? $itemStatuses[$item->getOrigData(RequestItemInterface::ITEM_STATUS)] : '';
                            $changes['after']['state'] = !empty($itemStatuses[$item->getItemStatus()])
                                ? $itemStatuses[$item->getItemStatus()]
                                : '';
                        }

                        if ((double)$item->getQty() != (double)$item->getOrigData(RequestItemInterface::QTY)) {
                            $changes['before']['qty'] = (double)$item->getOrigData(RequestItemInterface::QTY);
                            $changes['after']['qty'] = (double)$item->getQty();
                        }

                        if ($item->getReasonId() != $item->getOrigData(RequestItemInterface::REASON_ID)) {
                            $changes['before']['reason'] = $this->reasonInterface->load(
                                $item->getOrigData(RequestItemInterface::REASON_ID)
                            )->getTitle();
                            $changes['after']['reason'] = $this->reasonInterface->load(
                                $item->getReasonId()
                            )->getTitle();
                        }

                        if (!empty($changes)) {
                            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                            $changes['name'] = $orderItem->getName();
                            $changes['sku'] = $orderItem->getSku();
                            $items[] = $changes;
                        }
                    } else {
                        $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                        $splitItems[] = [
                            'name' => $orderItem->getName(),
                            'sku' => $orderItem->getSku(),
                            'state' => !empty($itemStatuses[$item->getItemStatus()])
                                ? $itemStatuses[$item->getItemStatus()]
                                : '',
                            'qty' => (double)$item->getQty(),
                            'reason' => $this->reasonInterface->load(
                                $item->getReasonId()
                            )->getTitle()
                        ];
                    }
                }

                if (!empty($before) || !empty($after) || !empty($items) || !empty($splitItems)) {
                    $this->createEvent->execute(
                        EventType::MANAGER_SAVED_RMA,
                        $request,
                        EventInitiator::MANAGER,
                        ['before' => $before, 'after' => $after, 'items' => $items, 'splited' => $splitItems]
                    );
                }
                break;
            case 'return_request_status_changed':
                $newStatus = $observer->getData('new_status');
                $this->createEvent->execute(
                    EventType::SYSTEM_CHANGED_STATUS,
                    $request,
                    EventInitiator::SYSTEM,
                    [
                        $this->orderReturnHelper->getStatusTitleById($observer->getData('original_status')),
                        $this->orderReturnHelper->getStatusTitleById($newStatus)
                    ]
                );
                $statusCode = $this->orderReturnHelper->getStatusCodeById($newStatus);
                $this->sendReturnSQSEvent($request, $statusCode);
                // trigger refund for return
                if ($request->getInstantRefund() == 1 && in_array($statusCode, ['picked_up', 'delivered'])) {
                    $result = $this->orderReturnHelper->triggerRefundForReturn($request, "REFUND_INITIATED");
                    if ($result) {
                        $statusId = $this->orderReturnHelper->getStatusId('refund_initiated');
                        $request->setRefundStatus($statusId);
                        $this->requestResource->save($request);
                    }
                }
                break;
            case 'return_request_refund_status_changed':
                $newStatus = $observer->getData('new_refund_status');
                $this->createEvent->execute(
                    EventType::MANAGER_CHANGED_REFUND_STATUS,
                    $request,
                    EventInitiator::MANAGER,
                    [
                        $this->orderReturnHelper->getStatusTitleById($observer->getData('original_refund_status')),
                        $this->orderReturnHelper->getStatusTitleById($newStatus)
                    ]
                );
                break;
        }
    }

    /**
     * Send Return SQS Events
     *
     * @param Request $request
     * @param string $status
     * @return void
     */
    public function sendReturnSQSEvent(Request $request, string $status): void
    {
        try {
            $emailData = [];
            $smsData = [];
            switch ($status) {
                case 'return_created':
                    $emailData = $this->getReturnDataForEmail($request, "RETURN_CREATED");
                    $smsData = $this->getOrderDataForSms($request, "RETURN_CREATED");
                    break;
                case 'return_approved':
                    $emailData = $this->getReturnDataForEmail($request, "RETURN_APPROVED");
                    $smsData = $this->getOrderDataForSms($request, "RETURN_APPROVED");
                    break;
                case 'out_for_pickup':
                    $emailData = $this->getReturnDataForEmail($request, "RETURN_OUT_FOR_PICKUP");
                    $smsData = $this->getOrderDataForSms($request, "RETURN_OUT_FOR_PICKUP");
                    break;
                case 'picked_up':
                    $emailData = $this->getReturnDataForEmail($request, "RETURN_PICKED_UP");
                    $smsData = $this->getOrderDataForSms($request, "RETURN_PICKED_UP");
                    break;
                case 'return_rejected':
                    $emailData = $this->getReturnDataForEmail($request, "RETURN_REJECTED");
                    $smsData = $this->getOrderDataForSms($request, "RETURN_REJECTED");
                    break;
            }

            if (!empty($emailData)) {
                $this->sqsEvent->sentEmailEventToSqs($emailData);
            }
            if (!empty($smsData)) {
                $this->sqsEvent->sentSmsEventToSqs($smsData);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get Return Request Data For Email Event
     *
     * @param Request $request
     * @param string $eventName
     * @return array
     * @throws LocalizedException
     */
    private function getReturnDataForEmail(Request $request, string $eventName): array
    {
        $order = $this->orderFactory->create()->load($request->getOrderId());
        $shippingAddress = $order->getShippingAddress();
        return [
            'type' => 'email',
            'event_name' => $eventName,
            'is_rvp' => true,
            'id' => $order->getId(),
            'name' => ucfirst($shippingAddress->getFirstname()) . " " . ucfirst($shippingAddress->getLastname()),
            'email' => $shippingAddress->getEmail(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone(),
            'shipping_address' => $this->getShippingAddressData($shippingAddress),
            'items' => $this->getReturnItemsData($request, $order),
            'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : '',
            'reject_reason' => $request->getRejectReasonId() ?
                $this->orderReturnHelper->getRejectReasonTitleById($request->getRejectReasonId())
                : ''
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
     * Get Return Items Data
     *
     * @param Request $request
     * @param Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function getReturnItemsData(Request $request, Order $order): array
    {
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $orderItemsMap = [];
        foreach ($order->getAllItems() as $item) {
            $orderItemsMap[$item->getId()] = $item;
        }

        $returnItemsData = [];

        foreach ($request->getRequestItems() as $returnItem) {
            $orderItemId = $returnItem->getOrderItemId();

            if (!isset($orderItemsMap[$orderItemId])) {
                continue;
            }
            $item = $orderItemsMap[$orderItemId];
            $product = $item->getProduct();

            $returnItemsData[] = [
                'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                'name' => $item->getName(),
                'qty' => (int)$returnItem->getRequestQty(),
                'cost' => $item->getBaseCost() ? number_format($item->getBaseCost(), 2) : 0,
                'price' => $item->getPrice() ? number_format($item->getPrice(), 2) : 0,
                'sku' => $item->getSku(),
                'reason' => $this->reasonInterface->load(
                    $returnItem->getReasonId()
                )->getTitle(),
            ];
        }
        return $returnItemsData;
    }

    /**
     * Get Order Return Data For Sms Event
     *
     * @param Request $request
     * @param string $eventName
     * @return array
     */
    private function getOrderDataForSms(Request $request, string $eventName): array
    {
        $order = $this->orderFactory->create()->load($request->getOrderId());
        $shippingAddress = $order->getShippingAddress();

        return [
            'type' => 'sms',
            'event_name' => $eventName,
            'name' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone()
        ];
    }
}
