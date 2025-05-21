<?php
/**
 * Pratech_CodRestriction
 *
 * @category  XML
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\CodRestriction\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Pratech\CodRestriction\Model\ResourceModel\CodOrderCounter\CollectionFactory;


class ResetCodCounts
{
    public const IS_CRON_ENABLED = 'cron_schedule/update_cod_order_count/status';

    /**
     * @param CronLogger                 $cronLogger
     * @param ScopeConfigInterface       $scopeConfig
     * @param ReturnCollectionFactory    $returnCollectionFactory
     * @param RequestRepositoryInterface $requestRepository
     * @param OrderReturnHelper          $orderReturnHelper
     */
    public function __construct(
        private CronLogger           $cronLogger,
        private ScopeConfigInterface $scopeConfig,
        private CollectionFactory    $collectionFactory,
        private TimezoneInterface    $timezone
    ) {
    }

    /**
     * Cron to update return instant refund status.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        $this->cronLogger->info('ResetCodCounts cron started at ' . date('Y-m-d H:i:s'));

        try {
            $collection = $this->collectionFactory->create();
            $now = $this->timezone->date();
            $today = $now->format('Y-m-d');
            $currentWeek = $now->format('oW');
            $currentMonth = $now->format('Y-m');

            foreach ($collection as $item) {
                try {
                    $updatedAt = $this->timezone->date($item->getUpdatedAt());
                    $updatedDay = $updatedAt->format('Y-m-d');
                    $updatedWeek = $updatedAt->format('oW');
                    $updatedMonth = $updatedAt->format('Y-m');

                    $resetDaily = $updatedDay !== $today;
                    $resetWeekly = $updatedWeek !== $currentWeek;
                    $resetMonthly = $updatedMonth !== $currentMonth;

                    if ($resetDaily) {
                        $item->setDailyCount(0);
                    }
                    if ($resetWeekly) {
                        $item->setWeeklyCount(0);
                    }
                    if ($resetMonthly) {
                        $item->setMonthlyCount(0);
                    }

                    if ($resetDaily || $resetWeekly || $resetMonthly) {
                        $item->save();
                        $this->cronLogger->info(
                            sprintf(
                                'COD count reset for customer_id %s: [daily: %s, weekly: %s, monthly: %s]',
                                $item->getCustomerId(),
                                $resetDaily ? 'yes' : 'no',
                                $resetWeekly ? 'yes' : 'no',
                                $resetMonthly ? 'yes' : 'no'
                            )
                        );
                    }
                } catch (Exception $exception) {
                    $this->cronLogger->error("Error updating cod counts for customer Id: {$item->getCustomerId()} | " . $exception->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->cronLogger->error('ResetCodCounts error: ' . $e->getMessage());
        }

        $this->cronLogger->info('ResetCodCounts cron ended at ' . date('Y-m-d H:i:s'));
    }
}
