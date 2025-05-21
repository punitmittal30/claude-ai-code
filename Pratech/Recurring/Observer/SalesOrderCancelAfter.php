<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Observer;

use Exception;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;
use Pratech\Recurring\Model\SubscriptionFactory;

class SalesOrderCancelAfter implements ObserverInterface
{
    /**
     * @param SubscriptionFactory $subscriptionFactory
     */
    public function __construct(
        private SubscriptionFactory $subscriptionFactory
    ) {
    }

    /**
     * Observer action for Sales order cancel after.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $orderId = $observer->getOrder()->getId();
            $subscriptionColllection = $this->subscriptionFactory->create()->getCollection();
            $subscriptionColllection->addFieldToFilter('order_id', $orderId);
            foreach ($subscriptionColllection as $model) {
                $this->setStatus($model, $model->getId());
            }
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Updates the status of the subscription
     *
     * @param object $model
     * @param integer $id
     */
    private function setStatus($model, $id)
    {
        $model->setStatus(SubscriptionStatus::CANCELLED)
            ->setValidTill(date('Y-m-d'))
            ->setId($id)
            ->save();
    }
}
