<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Cron;

use DateInterval;
use Exception;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;
use Pratech\StoreCredit\Model\CreditPointsFactory;

class UpdateStoreCredit
{
    /**
     * Is cron enabled for update store_credit.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/update_store_credit/status';

    /**
     * Add StoreCredit After Days Config.
     */
    public const USE_STORE_CREDIT_AFTER_DAYS = 'store_credit/store_credit/use_store_credit_after_days';

    /**
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezone
     * @param CustomerRedisCache $customerRedisCache
     * @param CreditPointsFactory $creditPointsFactory
     * @param StoreCreditHelper $storeCreditHelper
     */
    public function __construct(
        private CronLogger           $cronLogger,
        private ScopeConfigInterface $scopeConfig,
        private TimezoneInterface    $timezone,
        private CustomerRedisCache   $customerRedisCache,
        private CreditPointsFactory  $creditPointsFactory,
        private StoreCreditHelper    $storeCreditHelper
    ) {
    }

    /**
     * Cron to update storeCredit.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateStoreCredit cron started at ' . date('Y-m-d H:i:s'));
            try {
                $afterDays = $this->scopeConfig->getValue(
                    self::USE_STORE_CREDIT_AFTER_DAYS,
                    ScopeInterface::SCOPE_STORE
                );
                $currentDate = $this->timezone->date();
                $previousDay = $currentDate->sub(DateInterval::createFromDateString($afterDays . ' days'));

                // Set time to the beginning of the day
                $previousDay->setTime(05, 30, 00);

                $startTimestamp = $this->timezone->date($previousDay)->getTimestamp();

                // Set time to the end of the day
                $previousDay->setTime(29, 29, 59);

                $endTimestamp = $this->timezone->date($previousDay)->getTimestamp();

                $creditPoints = $this->creditPointsFactory->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'created_at',
                        [
                            'from' => date('Y-m-d H:i:s', $startTimestamp),
                            'to' => date('Y-m-d H:i:s', $endTimestamp)
                        ]
                    );
                foreach ($creditPoints as $creditPoint) {
                    if ($creditPoint->getCreditedStatus() == 0 && $creditPoint->getCanCredit() == 1
                        && $creditPoint->getCreditPoints() > 0
                    ) {
                        try {
                            $this->storeCreditHelper->addStoreCredit(
                                (int)$creditPoint->getCustomerId(),
                                (float)$creditPoint->getCreditPoints(),
                                $creditPoint->getAdditionalInfo() ?? '',
                                [
                                    'event_name' => 'order',
                                    'order_id' => (int)$creditPoint->getOrderId()
                                ]
                            );
                            $creditPointsModel = $this->creditPointsFactory->create();
                            $creditPointsModel->load($creditPoint->getStorecreditId())
                                ->setCreditedStatus(1)
                                ->save();
                            $this->customerRedisCache->deleteCustomerStoreCreditTransactions(
                                (int)$creditPoint->getCustomerId()
                            );
                        } catch (Exception $exception) {
                            $this->cronLogger->error('Order ID: ' . $creditPoint->getOrderId()
                                . ' Shipment ID: ' . $creditPoint->getShipmentId()
                                . ' Error: ' . $exception->getMessage() . __METHOD__);
                        }
                    }
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateStoreCredit cron ended at ' . date('Y-m-d H:i:s'));
        }
    }
}
