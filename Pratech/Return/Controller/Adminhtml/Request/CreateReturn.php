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

namespace Pratech\Return\Controller\Adminhtml\Request;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Pratech\Return\Api\CreateReturnProcessorInterface;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\ReturnOrderItemInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn;
use Pratech\Return\Model\OptionSource\ItemStatus;
use Psr\Log\LoggerInterface;

class CreateReturn extends Action
{
    public const ADMIN_RESOURCE = 'Pratech_Return::return_create';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param Action\Context $context
     * @param RequestRepositoryInterface $requestRepository
     * @param CreateReturnProcessorInterface $createReturnProcessor
     * @param LoggerInterface $logger
     * @param OrderReturn $orderReturnHelper
     * @param OrderFactory $orderFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        Action\Context                         $context,
        private RequestRepositoryInterface     $requestRepository,
        private CreateReturnProcessorInterface $createReturnProcessor,
        private LoggerInterface                $logger,
        private OrderReturn                    $orderReturnHelper,
        private OrderFactory                   $orderFactory,
        private ShipmentCollectionFactory      $shipmentCollectionFactory,
    ) {
        parent::__construct($context);
        $this->eventManager = $context->getEventManager() ?: ObjectManager::getInstance()->get(ManagerInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam(RequestInterface::ORDER_ID);
        $items = $this->getRequest()->getParam('return_items');
        $order = $this->orderFactory->create()->load($orderId);

        if (!$order->getId()) {
            throw new LocalizedException(__('Order not found.'));
        }

        if ($this->getRequest()->getParams() && $orderId && $items) {
            $refundStatusId = $this->orderReturnHelper->getInitialRefundStatusId();
            if ($returnOrder = $this->createReturnProcessor->process($orderId, true)) {
                $request = $this->requestRepository->getEmptyRequestModel();
                $request->setNote($this->getRequest()->getParam(RequestInterface::NOTE, ''))
                    ->setComment($this->getRequest()->getParam(RequestInterface::MESSAGE))
                    ->setStatus($this->getRequest()->getParam(RequestInterface::STATUS))
                    ->setCustomerId($returnOrder->getOrder()->getCustomerId())
                    ->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID))
                    ->setOrderId($orderId)
                    ->setRefundStatus($refundStatusId)
                    ->setShipmentId($this->getShipmentId($orderId))
                    ->setCustomerName(
                        $returnOrder->getOrder()->getBillingAddress()->getFirstname()
                        . ' ' . $returnOrder->getOrder()->getBillingAddress()->getLastname()
                    );

                $items = $this->processItems($order, $returnOrder->getItems(), $items);

                if ($items) {
                    $request->setRequestItems($items);

                    try {
                        $this->eventManager->dispatch(
                            'return_manager_rma_before_create',
                            ['request' => $request]
                        );
                        $this->requestRepository->save($request);
                        $order->setReturnRequests(
                            $order->getReturnRequests() ?
                                $order->getReturnRequests() . "," . $request->getRequestId()
                                : $request->getRequestId()
                        );
                        $order->save();
                        $this->eventManager->dispatch(
                            'return_request_created_by_admin',
                            ['request' => $request]
                        );

                        return $this->resultRedirectFactory->create()->setPath(
                            'return/request/view',
                            ['request_id' => $request->getRequestId()]
                        );
                    } catch (Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }
        return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
    }

    /**
     * Get Shipment Id by Order Id
     *
     * @param int $orderId
     * @return int
     */
    public function getShipmentId(int $orderId): int
    {
        $shipmentCollection = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId);

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection->getFirstItem()->getId();
        }

        return 0;
    }

    /**
     * @param Order $order
     * @param ReturnOrderItemInterface[] $orderItems
     * @param array $items
     *
     * @return RequestItemInterface[]
     */
    public function processItems(Order $order, array $orderItems, array $items)
    {
        $result = [];

        foreach ($items as $itemGroup) {
            if ($item = $this->processItemGroup($order, $orderItems, $itemGroup)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param Order $order
     * @param ReturnOrderItemInterface[] $orderItems
     * @param array $itemGroup
     *
     * @return RequestItemInterface|bool
     */
    public function processItemGroup(Order $order, array $orderItems, array $itemGroup)
    {
        foreach ($itemGroup as $item) {
            if (!empty($item[RequestItemInterface::REQUEST_ITEM_ID])
                && !empty($item[RequestItemInterface::QTY])
                && !empty($item[RequestItemInterface::REASON_ID])
                && $orderItem = $this->getOrderItemByOrderItemId(
                    $orderItems,
                    (int)$item[RequestItemInterface::ORDER_ITEM_ID]
                )
            ) {
                if ($orderItem->getAvailableQty() > 0.0001
                    && $orderItem->getAvailableQty() >= (double)$item[RequestItemInterface::QTY]
                ) {
                    if (!empty($item[RequestItemInterface::ITEM_STATUS])
                        && $item[RequestItemInterface::ITEM_STATUS] == 'true'
                    ) {
                        $itemStatus = ItemStatus::AUTHORIZED;
                    } else {
                        $itemStatus = 0;
                    }

                    $requestItem = $this->requestRepository->getEmptyRequestItemModel();
                    $requestItem->setItemStatus($itemStatus)
                        ->setOrderItemId($orderItem->getItem()->getItemId())
                        ->setReasonId($item[RequestItemInterface::REASON_ID])
                        ->setRequestQty($item[RequestItemInterface::QTY])
                        ->setQty($item[RequestItemInterface::QTY]);

                    $this->orderReturnHelper->updateQtyReturnedInOrderItem(
                        $order,
                        $item[RequestItemInterface::ORDER_ITEM_ID],
                        $item[RequestItemInterface::QTY]
                    );
                    return $requestItem;
                }
            }
        }

        return false;
    }

    /**
     * @param ReturnOrderItemInterface[] $orderItems
     * @param int $orderItemId
     *
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
}
