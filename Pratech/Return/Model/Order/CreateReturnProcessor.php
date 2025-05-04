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

namespace Pratech\Return\Model\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Pratech\Return\Api\CreateReturnProcessorInterface;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\ReturnOrderInterface;
use Pratech\Return\Api\Data\ReturnOrderInterfaceFactory;
use Pratech\Return\Api\Data\ReturnOrderItemInterface;
use Pratech\Return\Api\Data\ReturnOrderItemInterfaceFactory;
use Pratech\Return\Model\OptionSource\ItemStatus;
use Pratech\Return\Model\OptionSource\NoReturnableReasons;
use Pratech\Return\Model\Request\ResourceModel\Request;
use Pratech\Return\Model\Request\ResourceModel\RequestItemCollectionFactory;

class CreateReturnProcessor implements CreateReturnProcessorInterface
{
    /**
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ReturnOrderInterfaceFactory $returnOrderFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RequestItemCollectionFactory $requestItemCollectionFactory
     * @param ReturnOrderItemInterfaceFactory $returnOrderItemFactory
     */
    public function __construct(
        private OrderRepositoryInterface        $orderRepository,
        private ReturnOrderInterfaceFactory     $returnOrderFactory,
        private ProductRepositoryInterface      $productRepository,
        private RequestItemCollectionFactory    $requestItemCollectionFactory,
        private ReturnOrderItemInterfaceFactory $returnOrderItemFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function process($orderId, $isAdmin = false)
    {
        /**
         * @var ReturnOrderInterface $returnOrder
         */
        $returnOrder = $this->returnOrderFactory->create();
        $order = $this->orderRepository->get((int)$orderId);

        $returnOrder->setOrder($order);
        $items = [];
        $alreadyRequestedItem = $this->getAlreadyRequestedItems($order->getEntityId());

        foreach ($order->getItems() as $item) {
            if ($this->isDisallowProductType($item)) {
                continue;
            }

            /**
             * @var ReturnOrderItemInterface $returnItem
             */
            $returnItem = $this->returnOrderItemFactory->create();

            try {
                $product = $this->productRepository->get($item->getSku());
            } catch (NoSuchEntityException $exception) {
                $product = false;
            }

            if (!$item->getParentItemId()) {
                $qtyShipped = $item->getQtyShipped();
                $qtyCanceled = $item->getQtyCanceled();
                $qtyRefunded = $item->getQtyRefunded();
            } else {
                $qtyShipped = $item->getParentItem()->getQtyShipped();
                $qtyCanceled = $item->getParentItem()->getQtyCanceled();
                $qtyRefunded = $item->getParentItem()->getQtyRefunded();
            }

            $returnItem->setItem($item)
                ->setProductItem($product)
                ->setPurchasedQty($qtyShipped);

            $rmaQty = 0;

            if (isset($alreadyRequestedItem[$item->getItemId()]['qty'])) {
                $rmaQty = $alreadyRequestedItem[$item->getItemId()]['qty'];
            }

            if ($qtyShipped < 0.0001) {
                $returnItem->setIsReturnable(false)
                    ->setNoReturnableReason(NoReturnableReasons::ITEM_WASNT_SHIPPED);
                $items[] = $returnItem;

                continue;
            }

            $orderAvailableQty = $qtyShipped - $qtyCanceled;
            if (!$isAdmin) {
                $orderAvailableQty -= $qtyRefunded;
            }
            if ($orderAvailableQty - $rmaQty <= 0.0001) {
                if ($rmaQty == 0) {
                    $returnItem->setIsReturnable(false)
                        ->setNoReturnableReason(NoReturnableReasons::REFUNDED);
                } else {
                    $returnItem->setIsReturnable(false)
                        ->setNoReturnableReason(NoReturnableReasons::ALREADY_RETURNED)
                        ->setNoReturnableData($alreadyRequestedItem[$item->getItemId()]['requests']);
                }
            } else {
                $isAllowedParentProduct = true;

                if ($item->getParentItemId()) {
                    try {
                        $parentProduct = $this->productRepository->getById($item->getParentItem()->getProductId());
                    } catch (NoSuchEntityException $exception) {
                        $parentProduct = false;
                    }

                    if ($parentProduct) { // no reason to apply rules if no product, order will be checked with child
                        $parentReturnItem = $this->returnOrderItemFactory->create();
                        $parentReturnItem->setItem($item->getParentItem())
                            ->setProductItem($parentProduct)
                            ->setPurchasedQty($qtyShipped);

                    }
                }

                if ($isAdmin || ($isAllowedParentProduct)) {
                    $returnItem->setIsReturnable(true)
                        ->setAvailableQty($orderAvailableQty - $rmaQty);
                } elseif ($returnItem->getItem()->getPrice() !== (float)$returnItem->getItem()->getOriginalPrice()) {
                    $returnItem->setIsReturnable(false)
                        ->setNoReturnableReason(NoReturnableReasons::ITEM_WAS_ON_SALE);
                } else {
                    $returnItem->setIsReturnable(false)
                        ->setNoReturnableReason(NoReturnableReasons::EXPIRED_PERIOD);
                }
            }

            $items[] = $returnItem;
        }

        $returnOrder->setItems($items);

        return $returnOrder;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getAlreadyRequestedItems(int $orderId)
    {
        $requestItemCollection = $this->requestItemCollectionFactory->create();
        $requestItemCollection->join(
            Request::TABLE_NAME,
            'main_table.' . RequestItemInterface::REQUEST_ID
            . ' = ' . Request::TABLE_NAME . '.' . RequestInterface::REQUEST_ID
        )->addFieldToFilter(
            Request::TABLE_NAME . '.' . RequestInterface::ORDER_ID,
            (int)$orderId
        )->addFieldToSelect(
            [RequestItemInterface::ORDER_ITEM_ID, RequestItemInterface::REQUEST_ID, RequestItemInterface::QTY]
        )->addFieldToFilter(RequestItemInterface::ITEM_STATUS, ['neq' => ItemStatus::REJECTED]);

        // 37 = return_canceled status
        $requestItemCollection->addFieldToFilter(
            Request::TABLE_NAME . '.' . RequestInterface::STATUS,
            ['nin' => 37]
        );

        $previousItems = [];

        /**
         * @var RequestItemInterface $requestItem
         */
        foreach ($requestItemCollection->getData() as $requestItem) {
            if (!isset($previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]['qty'])) {
                $previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]['qty'] = 0;
            }

            $previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]['qty'] +=
                $requestItem[RequestItemInterface::QTY];

            if (!isset($previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]['requests'])) {
                $previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]['requests'] = [];
            }

            $previousItems[$requestItem[RequestItemInterface::ORDER_ITEM_ID]]
            ['requests'][$requestItem[RequestItemInterface::REQUEST_ID]] = [
                RequestInterface::REQUEST_ID => $requestItem[RequestItemInterface::REQUEST_ID]
            ];
        }

        return $previousItems;
    }

    /**
     * @param OrderItemInterface $item
     * @return bool
     */
    public function isDisallowProductType(OrderItemInterface $item): bool
    {
        if ($item->getData('has_children') || $item->getProductType() === 'downloadable') {
            return true;
        }

        return false;
    }
}
