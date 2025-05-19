<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Observer;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Helper\Config as StoreCreditConfig;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * Observer to update store credit after successfully register customer.
 */
class CustomerRegistration implements ObserverInterface
{
    /**
     * @param Logger $apiLogger
     * @param StoreCreditHelper $storeCreditHelper
     * @param CustomerRedisCache $customerRedisCache
     * @param StoreCreditConfig $storeCreditConfig
     */
    public function __construct(
        private Logger               $apiLogger,
        private StoreCreditHelper    $storeCreditHelper,
        private CustomerRedisCache   $customerRedisCache,
        private StoreCreditConfig    $storeCreditConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $isCustomerRegistrationCashbackEnabled = $this->storeCreditConfig->isRegistrationCashbackEnabled();

        if ($isCustomerRegistrationCashbackEnabled) {
            /** @var CustomerInterface $customer */
            $customer = $observer->getEvent()->getCustomer();
            $customerId = $customer->getId();
            $registrationSource = $customer->getCustomAttribute('registration_source')
                ? $customer->getCustomAttribute('registration_source')->getValue()
                : '';
            $amount = $this->storeCreditConfig->getRegistrationCashbackAmountBySource($registrationSource);
            $additionalInfo = $this->storeCreditConfig->getAdditionalInfoForRegistrationCashback();

            try {
                // Update store credit for the customer
                $this->storeCreditHelper->addStoreCredit(
                    $customerId,
                    $amount,
                    str_replace("%s", $registrationSource, $additionalInfo),
                    [
                        'event_name' => 'registration'
                    ]
                );

                $this->customerRedisCache->deleteCustomerStoreCreditTransactions($customerId);
            } catch (Exception $exception) {
                $this->apiLogger->error(
                    "Error in Store Credit after new customer registration with ID: "
                    . $customerId . " | " . $exception->getMessage()
                );
            }
        }
    }
}
