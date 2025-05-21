<?php
/**
 * Pratech_CodRestriction
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CodRestriction\Observer;

use DateTime;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Pratech\CodRestriction\Model\CodOrderCounterFactory;
use Magento\Sales\Model\Order;

class SalesOrderSaveAfter implements ObserverInterface
{

    /**
     * @param CodOrderCounterFactory $codOrderCounterFactory
     * @param TimezoneInterface      $timezone
     */
    public function __construct(
        protected CodOrderCounterFactory $codOrderCounterFactory,
        protected TimezoneInterface      $timezone
    ) {
    }

    /**
     * Update COD order count
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        $customerId = $order->getCustomerId();
        if ($order->getPayment()->getMethod() !== 'cashondelivery' || !$customerId) {
            return;
        }

        if ($order->getStatus() == 'pending') {
            $model = $this->codOrderCounterFactory->create()->load($customerId, 'customer_id');
            $now = $this->timezone->date();
            $nowDate = $now->format('Y-m-d');
            $nowWeek = $now->format('oW');
            $nowMonth = $now->format('Y-m');

            $updatedAt = $model->getUpdatedAt();

            if (!$model->getId() || !$updatedAt) {
                $model->setCustomerId($customerId)
                    ->setDailyCount(1)
                    ->setWeeklyCount(1)
                    ->setMonthlyCount(1)
                    ->setUpdatedAt($now->format('Y-m-d H:i:s'))
                    ->save();
                return;
            }

            $lastUpdate = $this->timezone->date(new DateTime($updatedAt));
            $lastDate = $lastUpdate->format('Y-m-d');
            $lastWeek = $lastUpdate->format('oW');
            $lastMonth = $lastUpdate->format('Y-m');

            $dailyCount = ($lastDate === $nowDate) ? $model->getDailyCount() + 1 : 1;
            $weeklyCount = ($lastWeek === $nowWeek) ? $model->getWeeklyCount() + 1 : 1;
            $monthlyCount = ($lastMonth === $nowMonth) ? $model->getMonthlyCount() + 1 : 1;

            $model->setCustomerId($customerId)
                ->setDailyCount($dailyCount)
                ->setWeeklyCount($weeklyCount)
                ->setMonthlyCount($monthlyCount)
                ->setUpdatedAt($now->format('Y-m-d H:i:s'));

            $model->save();
        } elseif ($order->getStatus() == 'canceled') {
            $model = $this->codOrderCounterFactory->create()->load($customerId, 'customer_id');
            if (!$model->getId()) {
                return;
            }

            $now = $this->timezone->date();
            $updatedAt = $this->timezone->date($model->getUpdatedAt());

            $currentDay = $now->format('Y-m-d');
            $currentWeek = $now->format('oW');
            $currentMonth = $now->format('Y-m');

            $recordDay = $updatedAt->format('Y-m-d');
            $recordWeek = $updatedAt->format('oW');
            $recordMonth = $updatedAt->format('Y-m');
            if ($currentDay === $recordDay) {
                $model->setDailyCount(max(0, $model->getDailyCount() - 1));
            }

            if ($currentWeek === $recordWeek) {
                $model->setWeeklyCount(max(0, $model->getWeeklyCount() - 1));
            }

            if ($currentMonth === $recordMonth) {
                $model->setMonthlyCount(max(0, $model->getMonthlyCount() - 1));
            }

            $model->setUpdatedAt($now->format('Y-m-d H:i:s'));
            $model->save();
        }
    }
}
