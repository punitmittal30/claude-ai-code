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

namespace Pratech\Return\Model\Request\DataProvider;

use DateMalformedStringException;
use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\HistoryRepositoryInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\OptionSource\Reason as ReasonOptions;
use Pratech\Return\Model\Order\OrderItemImage;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory;

class Form extends AbstractDataProvider
{
    /**
     * Return Request Data provider Constructor
     *
     * @param CollectionFactory        $collectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param UrlInterface $url
     * @param AddressRenderer $addressRenderer
     * @param RequestRepositoryInterface $requestRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param ProductRepositoryInterface $productRepository
     * @param OrderItemImage $orderItemImage
     * @param HttpRequest $httpRequest
     * @param TimezoneInterface $timezone
     * @param ReasonOptions $reasonOptions
     * @param OrderReturnHelper $orderReturnHelper
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param HistoryRepositoryInterface $historyRepository
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        private CollectionFactory           $collectionFactory,
        private OrderRepositoryInterface    $orderRepository,
        private UrlInterface                $url,
        private AddressRenderer             $addressRenderer,
        private RequestRepositoryInterface  $requestRepository,
        private GroupRepositoryInterface    $groupRepository,
        private ProductRepositoryInterface  $productRepository,
        private OrderItemImage              $orderItemImage,
        private HttpRequest                 $httpRequest,
        private TimezoneInterface           $timezone,
        private ReasonOptions               $reasonOptions,
        private OrderReturnHelper           $orderReturnHelper,
        private ShipmentRepositoryInterface $shipmentRepository,
        private HistoryRepositoryInterface  $historyRepository,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array                               $meta = [],
        array                               $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get Data,
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws DateMalformedStringException
     * @throws LocalizedException
     */
    public function getData()
    {
        $data = parent::getData();

        if (!$data['totalRecords']) {
            return [];
        }
        $request = $this->requestRepository->getById($data['items'][0][RequestInterface::REQUEST_ID]);
        $data[$request->getRequestId()] = $request->getData();

        $data[$request->getRequestId()][RequestInterface::CUSTOM_FIELDS] = [];

        $data[$request->getRequestId()][RequestInterface::REQUEST_ITEMS] = [];
        $order = $this->orderRepository->get($request->getOrderId());
        $shipment = $this->shipmentRepository->get($request->getShipmentId());

        $returnItems = [];
        foreach ($request->getRequestItems() as $requestItem) {
            foreach ($order->getItems() as $item) {
                if ($item->getItemId() == $requestItem->getOrderItemId()) {
                    break;
                }
            }

            try {
                $product = $this->productRepository->get($item->getSku());
                $url = $this->url->getUrl('catalog/product/edit', ['id' => $product->getId()]);
            } catch (NoSuchEntityException $e) {
                $url = false;
            }

            $itemData = [
                RequestItemInterface::REQUEST_ITEM_ID => $requestItem->getRequestItemId(),
                RequestItemInterface::ORDER_ITEM_ID => $requestItem->getOrderItemId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'status' => $requestItem->getItemStatus(),
                'is_decimal' => $item->getIsQtyDecimal(),
                'reason_id' => $requestItem->getReasonId(),
                'is_returnable' => true,
                'url' => $url,
                'item_images' => json_decode($requestItem->getImages()),
                RequestItemInterface::QTY => $requestItem->getQty(),
                'image' => $this->orderItemImage->getUrl($item->getItemId()),
                'refunded_amount' => $requestItem->getRefundedAmount(),
                'is_editable' => true
            ];
            if (empty($returnItems[$requestItem->getOrderItemId()])) {
                $itemData[RequestItemInterface::REQUEST_QTY] = $requestItem->getRequestQty();
            }
            $returnItems[$requestItem->getOrderItemId()][] = $itemData;
        }

        $data[$request->getRequestId()]['return_items'] = array_merge($returnItems);

        $data[$request->getRequestId()][RequestInterface::TRACKING_NUMBERS] = [];
        foreach ($request->getTrackingNumbers() as $trackingNumber) {
            $data[$request->getRequestId()][RequestInterface::TRACKING_NUMBERS][] = [
                'id' => $trackingNumber->getTrackingId(),
                'customer' => $trackingNumber->isCustomer(),
                'code' => $trackingNumber->getTrackingCode(),
                'number' => $trackingNumber->getTrackingNumber()
            ];
        }

        try {
            $customerGroup = $this->groupRepository->getById($order->getCustomerGroupId())->getCode();
        } catch (NoSuchEntityException $e) {
            $customerGroup = __('Customer group with specified ID %1 not found.', $order->getCustomerGroupId());
        }

        $data[$request->getRequestId()]['information'] = [
            'order' => [
                'entity_id' => $order->getEntityId(),
                'increment_id' => '#' . $order->getIncrementId(),
                'created' => $this->timezone->date(new DateTime($order->getCreatedAt()))->format('Y-m-d H:i:s'),
                'status' => $order->getStatus(),
                'link' => $this->url->getUrl(
                    'sales/order/view',
                    ['order_id' => $order->getEntityId()]
                )
            ],
            'shipment' => [
                'entity_id' => $shipment->getEntityId(),
                'increment_id' => '#' . $shipment->getIncrementId(),
                'created' => $this->timezone->date(new DateTime($shipment->getCreatedAt()))
                    ->format('Y-m-d H:i:s'),
                'status' => $shipment->getStatus(),
                'link' => $this->url->getUrl(
                    'sales/shipment/view',
                    ['shipment_id' => $shipment->getEntityId()]
                )
            ],
            'customer' => [
                'name' => $order->getShippingAddress()->getFirstname() . ' '
                    . $order->getShippingAddress()->getLastname(),
                'address' => $this->addressRenderer->format($order->getShippingAddress(), 'html'),
                'email' => $order->getShippingAddress()->getEmail(),
                'customer_group' => $customerGroup
            ]
        ];

        $paymentDetails = $this->orderReturnHelper->getPaymentDetailsByRequestId($request->getRequestId());
        if ($paymentDetails) {
            $data[$request->getRequestId()]['payment_details'] = [
                'payment_type' => $paymentDetails['payment_type'],
                'upi_id' => $paymentDetails['upi_id'],
                'account_number' => $paymentDetails['account_number'],
                'ifsc_code' => $paymentDetails['ifsc_code'],
                'account_holder_name' => $paymentDetails['account_holder_name'],
            ];
        }
        $data[$request->getRequestId()]['history'] = [];
        $history = $this->historyRepository->getRequestEvents($request->getRequestId());
        foreach ($history as $event) {
            $eventData = $event->getData();
            $eventData['event_date'] = $this->timezone->date(
                new DateTime($event->getEventDate())
            )->format('Y-m-d H:i:s');

            $data[$request->getRequestId()]['history'][] = $eventData;
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        if ($this->httpRequest->getParam('request_id')) {
            try {
                $meta['tracking_details']['children']['tracking']
                ['arguments']['data']['config']['carriers'] = $this->orderReturnHelper->getCarriers();

                //Items To Return Meta
                $meta['rma_return_order']['arguments']['data']['config']['header'] = [
                    __('Product'),
                    __('RMA Details'),
                    __('Refunded Amount'),
                    __('Return QTY'),
                    __('Approved'),
                    __('Delivered'),
                    __('Completed'),
                    __('Reject'),
                    __('Action')
                ];
                $meta['rma_return_order']['arguments']['data']['config']['reasons'] =
                    $this->reasonOptions->toOptionArray();
            } catch (Exception $e) {
                return [];
            }
        }
        return $meta;
    }
}
