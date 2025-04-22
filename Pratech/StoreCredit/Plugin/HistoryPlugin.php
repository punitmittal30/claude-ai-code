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

namespace Pratech\StoreCredit\Plugin;

use Magento\CustomerBalance\Model\Balance\History;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

class HistoryPlugin
{
    const CONFIG_PATH_EXPIRY_DAYS = 'store_credit/store_credit/expiry_days';

    /**
     * History plugin constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $dateTime
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private DateTime             $dateTime
    ) {
    }

    /**
     * Update expiry_date in store credit.
     *
     * @param  History $subject
     * @return void
     */
    public function beforeBeforeSave(History $subject): void
    {
        $balance = $subject->getBalanceModel();

        if ($balance->getAmountDelta() <= 0 
            || !in_array((int)$balance->getHistoryAction(), [History::ACTION_CREATED, History::ACTION_UPDATED], true)
        ) {
            return;
        }

        $days = (int)($balance->getExpiryDays() ?: $this->scopeConfig->getValue(
            self::CONFIG_PATH_EXPIRY_DAYS,
            ScopeInterface::SCOPE_STORE
        ));

        if ($days > 0) {
            $expiryDate = $this->dateTime->gmtDate('Y-m-d H:i:s', "+$days days");
            $subject->setData('expiry_date', $expiryDate);
        }
    }
}
