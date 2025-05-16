<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;

class ExpireBalances
{
    /**
     * Is cron enabled for update store_credit.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/expire_store_credit/status';

    public const ACTION_EXPIRED = 6;

    /**
     * @param ResourceConnection   $resource
     * @param CronLogger           $logger
     * @param DateTime             $dateTime
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRedisCache   $customerRedisCache
     */
    public function __construct(
        protected ResourceConnection $resource,
        protected CronLogger         $logger,
        protected DateTime           $dateTime,
        private ScopeConfigInterface $scopeConfig,
        private CustomerRedisCache   $customerRedisCache,
    ) {
    }

    /**
     * Cron to update ExpireBalances.
     *
     * @return void
     */
    public function execute()
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->logger->info('ExpireBalances cron started at ' . date('Y-m-d H:i:s'));

            $conn = $this->resource->getConnection();
            $now = $this->dateTime->gmtDate();
            $tHistory = $this->resource->getTableName('magento_customerbalance_history');
            $tBalance = $this->resource->getTableName('magento_customerbalance');

            try {

                $select = $conn->select()
                    ->from(
                        ['h' => $tHistory],
                        [
                            'history_id' => 'h.history_id',
                            'balance_id' => 'h.balance_id',
                            'expiry_date' => 'h.expiry_date',
                            'credit_delta' => 'h.balance_delta',
                            'prev_balance' => new \Zend_Db_Expr(
                                "h.balance_amount - h.balance_delta"
                            ),
                            'used_sum' => new \Zend_Db_Expr(
                                "(SELECT IFNULL(SUM(u.balance_delta), 0)
                                  FROM {$tHistory} AS u
                                  WHERE u.action = 3
                                    AND u.balance_id = h.balance_id
                                    AND u.updated_at >= h.updated_at
                                    AND u.updated_at <= h.expiry_date)"
                            )
                        ]
                    )
                    ->where('h.expiry_date IS NOT NULL')
                    ->where('h.expiry_date < ?', $now)
                    ->where('h.is_expired = 0')
                    ->where('h.action IN (1, 2)')
                    ->group(['h.history_id']);

                $batches = $conn->fetchAll($select);

                foreach ($batches as $batch) {
                    $balanceId = (int)$batch['balance_id'];
                    $creditDelta = (float)$batch['credit_delta'];
                    $prevBalance = (float)$batch['prev_balance'];
                    $usedSum = (float)$batch['used_sum'];

                    $availableCredit = $creditDelta + $prevBalance;
                    $unusedCredit = $prevBalance - abs($usedSum);
                    if ($unusedCredit >= 0) {
                        $toExpire = $creditDelta;
                    } else {
                        $toExpire = max(($creditDelta + $unusedCredit), 0);
                    }

                    $balance = $conn->fetchOne(
                        $conn->select()
                            ->from($tBalance, 'amount')
                            ->where('balance_id = ?', $balanceId)
                    );
                    if ($toExpire > 0 && $balance > 0) {
                        $conn->update(
                            $tBalance,
                            ['amount' => new \Zend_Db_Expr("GREATEST(amount - {$toExpire}, 0)")],
                            ['balance_id = ?' => $balanceId]
                        );

                        $balanceData = $conn->fetchRow(
                            $conn->select()
                                ->from($tBalance, ['customer_id', 'amount'])
                                ->where('balance_id = ?', $balanceId)
                        );

                        $conn->insert(
                            $tHistory,
                            [
                                'balance_id' => $balanceId,
                                'updated_at' => $now,
                                'action' => self::ACTION_EXPIRED,
                                'balance_amount' => $balanceData['amount'],
                                'balance_delta' => -$toExpire,
                                'additional_info' => "Expired credit",
                                'is_customer_notified' => 0,
                                'expiry_date' => null,
                                'is_expired' => 0
                            ]
                        );
                        $this->customerRedisCache->deleteCustomerStoreCreditTransactions(
                            (int)$balanceData['customer_id']
                        );
                        $this->logger->info("Expired {$toExpire} points for balance_id={$balanceId}.");
                    }

                    $conn->update(
                        $tHistory,
                        ['is_expired' => 1],
                        [
                            'history_id = ?' => $batch['history_id'],
                            'is_expired = ?' => 0
                        ]
                    );
                }
            } catch (Exception $e) {
                $this->logger->error("Store Credit Expiration Cron Error: " . $e->getMessage());
            }
            $this->logger->info('ExpireBalances cron ended at ' . date('Y-m-d H:i:s'));
        }
    }
}
