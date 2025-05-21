<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

 namespace Pratech\Coupon\Observer;

use Pratech\Coupon\Model\Indexer\PurchaseHistory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Sales\Model\Order;

/**
 * sales_order_save_after
 */
class UpdateIndex implements ObserverInterface
{

    /**
     * @var string[]
     */
    private $orderStates = [
        'payment_failed',
        'canceled',
        'closed'
    ];

    /**
     * Update Index Constructor
     *
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order && $this->isOrderValid($order)) {
            $indexer = $this->indexerRegistry->get(PurchaseHistory::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexRow((int)$order->getId());
            }
        }
    }

    /**
     * Is Order Valid
     *
     * @param  Order $order
     * @return boolean
     */
    private function isOrderValid(Order $order): bool
    {
        return $order->getId()
            && !$order->getCustomerIsGuest()
            && $order->getCustomerId()
            && $this->isOrderStateValid($order);
    }

    /**
     * Is Order State Valid
     *
     * @param  Order $order
     * @return boolean
     */
    private function isOrderStateValid(Order $order): bool
    {
        return !in_array($order->getState(), $this->orderStates);
    }
}
