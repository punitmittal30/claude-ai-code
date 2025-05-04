<?php

namespace Pratech\PrepaidDiscount\Plugin\Model\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class AddPrepaidDiscountToResponse
{
    /**
     * @param OrderExtensionFactory $extensionFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        private OrderExtensionFactory $extensionFactory,
        private OrderFactory          $orderFactory
    ) {
    }

    /**
     * Add "prepaid_discount" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $orderSearchResult
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface   $subject,
        OrderSearchResultInterface $orderSearchResult
    ): OrderSearchResultInterface {
        foreach ($orderSearchResult->getItems() as $order) {
            $this->setPrepaidDiscount($order);
        }
        return $orderSearchResult;
    }

    /**
     * Set "prepaid_discount" to order data
     *
     * @param OrderInterface $order
     * @return void
     */
    public function setPrepaidDiscount(OrderInterface $order): void
    {
        if ($order instanceof Order) {
            $prepaidDiscount = $order->getPrepaidDiscount();
            $basePrepaidDiscount = $order->getBasePrepaidDiscount();
        } else {
            $orderModel = $this->orderFactory->create();
            $orderModel->load($order->getId());
            $prepaidDiscount = $order->getPrepaidDiscount();
            $basePrepaidDiscount = $order->getBasePrepaidDiscount();
        }

        $extensionAttributes = $order->getExtensionAttributes();
        $orderExtensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

        $orderExtensionAttributes->setPrepaidDiscount($prepaidDiscount);
        $orderExtensionAttributes->setBasePrepaidDiscount($basePrepaidDiscount);

        $order->setExtensionAttributes($orderExtensionAttributes);
    }

    /**
     * Add "prepaid_discount" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $resultOrder
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface           $resultOrder
    ) {
        $this->setPrepaidDiscount($resultOrder);
        return $resultOrder;
    }
}
