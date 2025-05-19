<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;

/**
 * Observer to update customer store credit cache on customer save after event.
 */
class CustomerSaveAfter implements ObserverInterface
{
    /**
     * Update Customer Store Credit Cache Constructor
     *
     * @param CustomerRedisCache $customerRedisCache
     * @param RedisCacheLogger $redisCacheLogger
     */
    public function __construct(
        private CustomerRedisCache $customerRedisCache,
        private RedisCacheLogger   $redisCacheLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $customer = $observer->getEvent()->getCustomer();
        try {
            if ($customer->getId()) {
                $this->customerRedisCache->deleteCustomerStoreCreditTransactions($customer->getId());
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
