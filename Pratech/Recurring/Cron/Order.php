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
namespace Pratech\Recurring\Cron;

use Exception;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Pratech\Base\Logger\CronLogger;
use Pratech\Recurring\Api\SubscriptionRepositoryInterface;
use Pratech\Recurring\Helper\Order as RecurringOrderHelper;
use Pratech\Recurring\Model\Config\Source\Duration as RecurringDuration;
use Pratech\Recurring\Model\Config\Source\DurationType as RecurringDurationType;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;
use Pratech\Recurring\Model\Subscription;
use Pratech\Recurring\Model\SubscriptionFactory;
use Pratech\Recurring\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use Pratech\Recurring\Model\SubscriptionMappingFactory;

class Order
{
    /**
     * @param OrderFactory $orderFactory
     * @param Date $date
     * @param CronLogger $cronLogger
     * @param RecurringOrderHelper $recurringOrderHelper
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionMappingFactory $subscriptionMappingFactory
     */
    public function __construct(
        private OrderFactory $orderFactory,
        private Date $date,
        private CronLogger $cronLogger,
        private RecurringOrderHelper $recurringOrderHelper,
        private SubscriptionFactory $subscriptionFactory,
        private SubscriptionCollectionFactory $subscriptionCollectionFactory,
        private SubscriptionMappingFactory $subscriptionMappingFactory
    ) {
    }
    
    /**
     * Cron job executed 1 time per hour to check the offline recurring orders creation
     */
    public function recurringOrder()
    {
        try {
            $subscriptionCollection = $this->subscriptionCollectionFactory->create()
                ->addFieldToFilter("status", SubscriptionStatus::ENABLED);
            foreach ($subscriptionCollection as $subscription) {
                $this->reProcessSubscription($subscription);
            }
        } catch (Exception $e) {
            $this->cronLogger->debug($e->getMessage() . __METHOD__);
            $this->cronLogger->error($e->getMessage() . __METHOD__);
        }
    }

    /**
     * Re processing subscription
     *
     * @param Subscription $subscription
     */
    private function reProcessSubscription(Subscription $subscription)
    {
        $canOrder = $this->canOrder($subscription);
        $order = $this->orderFactory->create()->load($subscription->getOrderId());
        if ($canOrder) {
            $this->createOrder($order, $subscription);
        }
    }

    /**
     * Create order
     *
     * @param SalesOrder $order
     * @param Subscription $subscription
     */
    private function createOrder(SalesOrder $order, Subscription $subscription)
    {
        try {
            $result = $this->recurringOrderHelper->createMageOrder($order, $subscription);
            if (isset($result['error']) && $result['error'] == 0) {
                $this->saveMapping($result['order_id'], $subscription->getId());
                $this->updateSubscription($subscription);
            } else {
                $this->cronLogger->error('Subscription ID: ' . $subscription->getId() . ' | '
                    . $result['msg'] . __METHOD__);
            }
        } catch (Exception $e) {
            $this->cronLogger->error('Subscription ID: ' . $subscription->getId() . ' | '
                . $e->getMessage() . __METHOD__);
        }
    }

    /**
     * This will decide plan should renew or not.
     *
     * @param Subscription $subscription
     * @return array
     */
    private function canOrder(Subscription $subscription)
    {
        $canOrder = false;
        $todayDate = date('Y-m-d');
        $mappingCollection = $this->subscriptionMappingFactory->create()->getCollection()
            ->addFieldToFilter('subscription_id', $subscription->getId())
            ->addFieldToFilter('created_at', ['like' => $todayDate.'%']);
        if ($mappingCollection->getSize()) {
            return $canOrder;
        }

        if ($subscription->getValidTill() == $todayDate) {
            $canOrder = true;
        }
        return $canOrder;
    }

    /**
     * This function is used for mapping the child order with the subscription
     *
     * @param integer $orderId
     * @param integer $subscriptionId
     */
    private function saveMapping($orderId, $subscriptionId)
    {
        $time = date('Y-m-d H:i:s');
        $model = $this->subscriptionMappingFactory->create();
        $model->setSubscriptionId($subscriptionId);
        $model->setOrderId($orderId);
        $model->setCreatedAt($time);
        $model->save();
    }

    /**
     * This function is used for updating the subscription data after child order creation
     *
     * @param Subscription $subscription
     */
    private function updateSubscription($subscription)
    {
        $mappingCollection = $this->subscriptionMappingFactory->create()->getCollection()
            ->addFieldToFilter('subscription_id', $subscription->getId());
        if ($mappingCollection->getSize() >= $subscription->getMaxRepeat()) {
            $subscription->setStatus(SubscriptionStatus::DISABLED);
        } else {
            $duration = $subscription->getDuration();
            $durationType = $subscription->getDurationType();
            $validTill = $this->getValidTill($duration, $durationType);
            $subscription->setValidTill($validTill);
        }
        $subscription->setId($subscription->getId())->save();
    }

    /**
     * Get Valid Till Date of subscription
     *
     * @param string $duration
     * @param string $durationType
     * @return string
     */
    private function getValidTill($duration, $durationType)
    {
        $validTill = '';
        switch ($durationType) {
            case RecurringDurationType::DAY:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::DAY));
                break;
            case RecurringDurationType::WEEK:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::WEEK));
                break;
            case RecurringDurationType::MONTH:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::MONTH));
                break;
            case RecurringDurationType::YEAR:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::YEAR));
                break;
        }
        return $validTill;
    }
}
