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

namespace Pratech\CodRestriction\Helper;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\CodRestriction\Model\CodOrderCounterFactory;

/**
 * Product helper class to provide data to catalog api endpoints.
 */
class Data
{
    public const CODRESTRICTION_STATUS_CONFIG_PATH = 'codrestriction/general/status';
    public const DAILY_LIMIT_CONFIG_PATH = 'codrestriction/general/daily_limit';
    public const WEEKLY_LIMIT_CONFIG_PATH = 'codrestriction/general/weekly_limit';
    public const MONTHLY_LIMIT_CONFIG_PATH = 'codrestriction/general/monthly_limit';

    /**
     * @param Logger                 $logger
     * @param ScopeConfigInterface   $scopeConfig
     * @param CodOrderCounterFactory $codOrderCounterFactory
     */
    public function __construct(
        private Logger                 $logger,
        private ScopeConfigInterface   $scopeConfig,
        private CodOrderCounterFactory $codOrderCounterFactory
    ) {
    }

    /**
     * Get System Config
     *
     * @param  string $configPath
     * @return mixed
     */
    public function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if COD is allowed for a customer
     *
     * @param  int $customerId
     * @return bool
     */
    public function isCodAllowedForCustomer(int $customerId): bool
    {
        try {
            if (!$this->getConfig(self::CODRESTRICTION_STATUS_CONFIG_PATH)) {
                return true;
            }

            $counter = $this->codOrderCounterFactory->create()
                ->load($customerId, 'customer_id');

            if (!$counter || !$counter->getId()) {
                return true;
            }

            if ((bool)$counter->getData('is_cod_disabled') === true) {
                return false;
            }

            $dailyLimit = (int)$this->getConfig(self::DAILY_LIMIT_CONFIG_PATH);
            $weeklyLimit = (int)$this->getConfig(self::WEEKLY_LIMIT_CONFIG_PATH);
            $monthlyLimit = (int)$this->getConfig(self::MONTHLY_LIMIT_CONFIG_PATH);

            $dailyCount = (int)$counter->getData('daily_count');
            $weeklyCount = (int)$counter->getData('weekly_count');
            $monthlyCount = (int)$counter->getData('monthly_count');

            if (($dailyLimit && $dailyCount >= $dailyLimit)
                || ($weeklyLimit && $weeklyCount >= $weeklyLimit)
                || ($monthlyLimit && $monthlyCount >= $monthlyLimit)
            ) {
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error('COD restriction check failed: ' . $e->getMessage());
        }
        return true;
    }
}
