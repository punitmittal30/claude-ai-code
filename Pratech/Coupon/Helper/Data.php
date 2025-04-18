<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const ERRORMESSAGE_ENABLED = 'coupon/general/enable';

    public const COUPON_EXIST = 'coupon/general/coupon_exist';

    public const CONDITION_FAILED = 'coupon/general/condition_fail';

    public const COUPON_STACKABLE = 'coupon/general/coupon_stackable';

    public const COUPON_EXPIRED = 'coupon/general/coupon_expired';

    public const COUPON_WEBSITE_ID = 'coupon/general/coupon_website_id';

    public const COUPON_USAGES = 'coupon/general/coupon_usages';

    public const CUSTOMER_GROUP = 'coupon/general/coupon_customer_group';

    public const MAX_COUPON_LIMIT_REACHED = 'coupon/general/max_coupon_stackable';

    /**
     * Coupon Error Messaging is enabled or not.
     *
     * @param string $scope
     * @return bool
     */
    public function isEnabled(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): bool
    {
        return $this->scopeConfig->isSetFlag(self::ERRORMESSAGE_ENABLED, $scope);
    }

    /**
     * Is Coupon Exists.
     *
     * @param string $scope
     * @return string
     */
    public function isCouponExits(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::COUPON_EXIST, $scope);
    }

    /**
     * Is Condition Fails?
     *
     * @param string $scope
     * @return string
     */
    public function isConditionFail(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::CONDITION_FAILED, $scope);
    }

    /**
     * Coupon Stackable Error Message.
     *
     * @param string $scope
     * @return string|null
     */
    public function stackableErrorMessage(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): ?string
    {
        return $this->scopeConfig->getValue(self::COUPON_STACKABLE, $scope);
    }

    /**
     * Is Coupon Expired?
     *
     * @param string $scope
     * @return string
     */
    public function isCouponExpired(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::COUPON_EXPIRED, $scope);
    }

    /**
     * Is Coupon applicable for this Website
     *
     * @param string $scope
     * @return string
     */
    public function isCouponWebsite(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::COUPON_WEBSITE_ID, $scope);
    }

    /**
     * Is Maximum Limit Reached for coupon stacking.
     *
     * @param string $scope
     * @return string
     */
    public function isMaxCouponLimitReached(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::MAX_COUPON_LIMIT_REACHED, $scope);
    }

    /**
     * Is Coupon Usage Exceeded?
     *
     * @param string $scope
     * @return string
     */
    public function isCouponUsage(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::COUPON_USAGES, $scope);
    }

    /**
     * Is Coupon Customer Group?
     *
     * @param string $scope
     * @return string
     */
    public function isCouponCustomerGroup(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return $this->scopeConfig->getValue(self::CUSTOMER_GROUP, $scope);
    }

    /**
     * Maximum stackable coupons.
     *
     * @return int
     */
    public function getMaximumNoOfCoupons(): int
    {
        return (int)$this->scopeConfig->getValue('coupon/stacking/max_coupon', ScopeInterface::SCOPE_STORE);
    }
}
