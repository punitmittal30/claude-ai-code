<?php

namespace Pratech\CustomDeliveryCharges\Plugin\Model\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class AddDeliveryChargesToResponse
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
     * Add "delivery_charges" extension attribute to order data object to make it accessible in API data
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
            $this->setDeliveryCharges($order);
        }
        return $orderSearchResult;
    }

    /**
     * Set "delivery_charges" to order data
     *
     * @param OrderInterface $order
     * @return void
     */
    public function setDeliveryCharges(OrderInterface $order): void
    {
        if ($order instanceof Order) {
            $deliveryCharges = $order->getDeliveryCharges();
            $baseDeliveryCharges = $order->getBaseDeliveryCharges();
//            $deliveryChargesRefunded = $order->getDeliveryChargesRefunded();
        } else {
            $orderModel = $this->orderFactory->create();
            $orderModel->load($order->getId());
            $deliveryCharges = $orderModel->getDeliveryCharges();
            $baseDeliveryCharges = $orderModel->getBaseDeliveryCharges();
//            $deliveryChargesRefunded = $order->getDeliveryChargesRefunded();
        }

        $extensionAttributes = $order->getExtensionAttributes();
        $orderExtensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

        $orderExtensionAttributes->setDeliveryCharges($deliveryCharges);
        $orderExtensionAttributes->setBaseDeliveryCharges($baseDeliveryCharges);
//        $orderExtensionAttributes->setDeliveryChargesRefunded($deliveryChargesRefunded);
        $order->setShippingAmount($deliveryCharges);
        $order->setBaseShippingAmount($baseDeliveryCharges);
//        $order->setShippingRefunded($deliveryChargesRefunded);

        $order->setExtensionAttributes($orderExtensionAttributes);
    }

    /**
     * Add "delivery_charges" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $resultOrder
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface           $resultOrder
    ) {
        $this->setDeliveryCharges($resultOrder);
        return $resultOrder;
    }
}
