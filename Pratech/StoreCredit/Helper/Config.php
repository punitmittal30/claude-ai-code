<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{

    /** Constants for Store Credit Configs  */
    private const IS_REGISTRATION_CASHBACK_ENABLED = 'store_credit/registration_store_credit/enabled';

    private const REGISTRATION_CASHBACK_AMOUNT = 'store_credit/registration_store_credit/store_credit_amount';

    private const REGISTRATION_CASHBACK_ADDITIONAL_INFO = 'store_credit/registration_store_credit/additional_info';

    private const IS_FIRST_REVIEW_CASHBACK_ENABLED = 'store_credit/first_review_store_credit/enabled';

    private const FIRST_REVIEW_CASHBACK_AMOUNT = 'store_credit/first_review_store_credit/store_credit_amount';

    private const FIRST_REVIEW_CASHBACK_ADDITIONAL_INFO = 'store_credit/first_review_store_credit/additional_info';

    private const CONVERSION_RATE_CONFIG_PATH = 'store_credit/store_credit/conversion_rate';


    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get Conversion Rate for store credit.
     *
     * @return mixed
     */
    public function getConversionRate(): mixed
    {
        return $this->getConfig(self::CONVERSION_RATE_CONFIG_PATH);
    }

    /**
     * Is Registration Cashback Enabled.
     *
     * @return bool
     */
    public function isRegistrationCashbackEnabled(): bool
    {
        return (bool)$this->getConfig(self::IS_REGISTRATION_CASHBACK_ENABLED);
    }

    /**
     * Get System Config
     *
     * @param string $configPath
     * @return mixed
     */
    private function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Additional Info For Registration Cashback.
     *
     * @return mixed
     */
    public function getAdditionalInfoForRegistrationCashback(): mixed
    {
        return $this->getConfig(self::REGISTRATION_CASHBACK_ADDITIONAL_INFO);
    }

    /**
     * Is First Review Cashback Enabled.
     *
     * @return bool
     */
    public function isFirstReviewCashbackEnabled(): bool
    {
        return (bool)$this->getConfig(self::IS_FIRST_REVIEW_CASHBACK_ENABLED);
    }

    /**
     * Get Additional Info For First Review Cashback.
     *
     * @return mixed
     */
    public function getAdditionalInfoForFirstReviewCashback(): mixed
    {
        return $this->getConfig(self::FIRST_REVIEW_CASHBACK_ADDITIONAL_INFO);
    }

    /**
     * Get Registration Cashback Amounts.
     *
     * @return array
     */
    public function getRegistrationCashbackAmounts(): array
    {
        $values = [];
        $registrationCashbackAmount = $this->getConfig(self::REGISTRATION_CASHBACK_AMOUNT);
        if ($registrationCashbackAmount) {
            $items = json_decode($registrationCashbackAmount, true);
            foreach ($items as $item) {
                $values[] = [
                    'registration_source' => $item['registration_source'],
                    'amount' => $item['amount']
                ];
            }
        }
        return $values;
    }

    /**
     * Get Registration Cashback Amount By Source.
     *
     * @param string|null $registrationSource
     * @return int
     */
    public function getRegistrationCashbackAmountBySource(?string $registrationSource): int
    {
        $registrationCashbackAmount = $this->getConfig(self::REGISTRATION_CASHBACK_AMOUNT);
        if ($registrationCashbackAmount && $registrationSource !== null) {
            $items = json_decode($registrationCashbackAmount, true);
            foreach ($items as $item) {
                if ($item['registration_source'] == $registrationSource) {
                    return (int)$item['amount'];
                }
            }
        }
        return 0;
    }

    /**
     * Get First Review Cashback Amount.
     *
     * @return int
     */
    public function getFirstReviewCashbackAmount(): int
    {
        return (int)$this->getConfig(self::FIRST_REVIEW_CASHBACK_AMOUNT);
    }
}
