<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;

/**
 * Observer to update customer purchased products cache on sales order save after event.
 */
class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * Update Customer Purchased Products Cache Constructor
     *
     * @param RedisCacheLogger $redisCacheLogger
     * @param CustomerRedisCache $customerRedisCache
     */
    public function __construct(
        private RedisCacheLogger   $redisCacheLogger,
        private CustomerRedisCache $customerRedisCache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        try {
            if ($order->getCustomerId()) {
                $this->customerRedisCache->deleteCustomerPurchasedProducts($order->getCustomerId());
                $this->customerRedisCache->deleteCustomerStoreCreditTransactions($order->getCustomerId());
                $this->customerRedisCache->deleteCustomerWidget($order->getCustomerId());
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
