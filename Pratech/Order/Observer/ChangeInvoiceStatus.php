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

namespace Pratech\Order\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class ChangeInvoiceStatus implements ObserverInterface
{
    /**
     * Partially Shipped Status for Order Shipped
     */
    public const STATUS_PARTIALLY_SHIPPED = 'partially_shipped';

    /**
     * Change Invoice Status Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $order->setState(Order::STATE_PROCESSING)->setStatus(self::STATUS_PARTIALLY_SHIPPED);
        $order->addCommentToStatusHistory("Vinculum: Invoice Created | Invoice ID: " . $invoice->getIncrementId());
        $this->orderRepository->save($order);
    }
}
