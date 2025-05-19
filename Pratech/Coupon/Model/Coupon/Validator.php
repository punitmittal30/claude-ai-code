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

namespace Pratech\Coupon\Model\Coupon;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Customer;
use Magento\SalesRule\Model\Rule\CustomerFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Utility;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Cart\Model\Config\Source\PlatformUsed;
use Pratech\Coupon\Helper\Data;
use Pratech\Coupon\Helper\Data as ConfigData;

class Validator
{
    /**
     * @var Rule $_rule
     */
    protected $_rule;

    /**
     * Coupon Validator Constructor.
     *
     * @param CouponFactory $couponFactory
     * @param Data $configData
     * @param TimezoneInterface $date
     * @param RuleFactory $ruleFactory
     * @param StoreManagerInterface $storeManager
     * @param UsageFactory $usage
     * @param DataObjectFactory $objectFactory
     * @param CustomerFactory $customerFactory
     * @param Utility $utility
     * @param PlatformUsed $platformUsed
     * @param TimezoneInterface $timezoneInterface
     * @param CollectionFactory $salesRuleCollectionFactory
     */
    public function __construct(
        private CouponFactory         $couponFactory,
        private ConfigData            $configData,
        private TimezoneInterface     $date,
        private RuleFactory           $ruleFactory,
        private StoreManagerInterface $storeManager,
        private UsageFactory          $usage,
        private DataObjectFactory     $objectFactory,
        private CustomerFactory       $customerFactory,
        private Utility               $utility,
        private PlatformUsed          $platformUsed,
        private TimezoneInterface     $timezoneInterface,
        private CollectionFactory     $salesRuleCollectionFactory
    ) {
    }

    /**
     * Validate Coupon Code
     *
     * @param Quote $quote
     * @param string $couponCode
     * @param string $platform
     * @param int|null $customerId
     * @return array|string|string[]
     * @throws NoSuchEntityException
     */
    public function validateCoupon(
        Quote  $quote,
        string $couponCode,
        string $platform,
        int    $customerId = null
    ): array|string {
        $msg = "";
        $coupon = $this->couponFactory->create();
        $coupon->load($couponCode, 'code');

        // Check if Coupon exists or not
        if (empty($coupon->getData())) {
            $msg = $this->configData->isCouponExits();
            return str_replace("%s", $couponCode, $msg);
        } else {
            $rule = $this->getRule($coupon->getRuleId());

            // Check for coupon status
            if (!$rule->getIsActive()) {
                $msg = $this->configData->isCouponExits();
                return str_replace("%s", $couponCode, $msg);
            }

            // Check for Platform applicability.
            $platformApplicable = $rule->getPlatformApplicable();
            if (!in_array($platformApplicable, [$this->getPlatformValue($platform), 3])) {
                return str_replace("%s", $couponCode, $msg);
            }

            // Check for Coupon Expiry
            $couponExpiry = $this->checkExpiry($rule);
            if ($couponExpiry) {
                $msg = $this->configData->isCouponExpired();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validation for Customer Group
            $isCouponApplicableForGuest = $this->validateIsValidForGuest($rule, $customerId);
            if (!$isCouponApplicableForGuest) {
                $msg = $this->configData->isCouponCustomerGroup();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validation for Website
            $couponWebsite = $this->validateCurrentWebsite($rule);
            if ($couponWebsite) {
                $msg = $this->configData->isCouponWebsite();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validate the number of usages
            $couponUsages = $this->validateCouponUsages($coupon, $customerId);
            if ($couponUsages) {
                $msg = $this->configData->isCouponUsage();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validate Cart Condition
            $couponCondition = $this->validateCouponCondition($quote, $coupon);
            if ($couponCondition) {
                $msg = $this->configData->isConditionFail();
                return str_replace("%s", $couponCode, $msg);
            }

            $alreadyAppliedCoupons = $quote->getCouponCode() ? explode(',', $quote->getCouponCode()) : [];

            if (count($alreadyAppliedCoupons) == $this->configData->getMaximumNoOfCoupons()) {
                $msg = $this->configData->isMaxCouponLimitReached();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validate Stackable Coupon
            $isCouponStackable = $this->isCouponStackableByRule($couponCode, $alreadyAppliedCoupons);
            if (!$isCouponStackable) {
                $msg = $this->configData->stackableErrorMessage();
                return str_replace("%s", $couponCode, $msg);
            }
        }
        return $msg;
    }

    /**
     * Get Rule
     *
     * @param int $ruleId
     * @return mixed
     */
    protected function getRule(int $ruleId): mixed
    {
        if (empty($this->_rule)) {
            $rule = $this->ruleFactory->create()->load($ruleId);
            if (!empty($rule)) {
                $this->_rule = $rule;
            }
        }
        return $this->_rule;
    }

    /**
     * Get Platform Used
     *
     * @param string|null $platform
     * @return string|mixed
     */
    private function getPlatformValue(string $platform = null): mixed
    {
        if ($platform) {
            foreach ($this->platformUsed->toOptionArray() as $option) {
                if (!strcasecmp($option['label'], $platform)) {
                    return $option['value'];
                }
            }
        }
        return "";
    }

    /**
     * Check Coupon Expiry.
     *
     * @param Rule $rule
     * @return bool
     */
    protected function checkExpiry(Rule $rule): bool
    {
        $couponDate = $rule->getToDate();
        $now = $this->date->date()->format('Y-m-d');
        if (!(empty($couponDate)) && strtotime($couponDate) < strtotime($now)) {
            return true;
        }
        return false;
    }

    /**
     * Check if coupon is assigned to current customer group
     *
     * @param Rule $rule
     * @param int|null $customerId
     * @return bool
     */
    protected function validateIsValidForGuest(Rule $rule, ?int $customerId): bool
    {
        $guestCustomerGroup = 0;
        if ($customerId == null) {
            if (in_array($guestCustomerGroup, $rule->getCustomerGroupIds())) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Current Website
     *
     * @param Rule $rule
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function validateCurrentWebsite(Rule $rule): bool
    {
        $currentWebsite = $this->storeManager->getStore()->getWebsiteId();
        if (!in_array($currentWebsite, $rule->getWebsiteIds())) {
            return true;
        }
        return false;
    }

    /**
     * Validate Coupon Usage
     *
     * @param Coupon $coupon
     * @param int|null $customerId
     * @return bool
     */
    protected function validateCouponUsages(Coupon $coupon, ?int $customerId): bool
    {
        // check entire usage limit
        if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
            return true;
        }
        // check per customer usage limit
        if ($customerId && $coupon->getUsagePerCustomer()) {
            $couponUsage = $this->objectFactory->create();
            $this->usage->create()->loadByCustomerCoupon(
                $couponUsage,
                $customerId,
                $coupon->getId()
            );
            if ($couponUsage->getCouponId() &&
                $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
            ) {
                return true;
            }
        }
        $rule = $this->getRule($coupon->getRuleId());
        $ruleId = $rule->getId();
        if ($ruleId && $rule->getUsesPerCustomer()) {
            /** @var Customer $ruleCustomer */
            $ruleCustomer = $this->customerFactory->create();
            $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
            if ($ruleCustomer->getId()) {
                if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * NEW: Validate Coupon Condition.
     *
     * @param Quote $quote
     * @param Coupon $coupon
     * @return bool
     */
    protected function validateCouponCondition(Quote $quote, Coupon $coupon): bool
    {
        $rule = $this->getRule($coupon->getRuleId());
        $address = $quote->getShippingAddress();

        // Cart Level Check
        $validate = $this->utility->canProcessRule($rule, $address);

        if (!$validate) {
            return true;
        } else {
            // Item level check
            $items = $quote->getAllVisibleItems();
            $validAction = false;
            foreach ($items as $item) {
                if ($validAction = $rule->getActions()->validate($item)) {
                    $validAction = true;
                    break;
                }
            }
            if (!$validAction) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if coupon is stackable or not
     *
     * @param string|null $couponCode
     * @param array $alreadyAppliedCodes
     * @return bool
     */
    public function isCouponStackableByRule(?string $couponCode, array $alreadyAppliedCodes): bool
    {
        $ruleIdToApply = '';
        $stackableRuleIds = [];
        if (!empty($alreadyAppliedCodes)) {
            if ($couponCode !== null) {
                try {
                    $ruleIdToApply = $this->getSalesRuleByCouponCode($couponCode)->getId();
                } catch (NoSuchEntityException) {
                    return false;
                }
            }
            foreach ($alreadyAppliedCodes as $alreadyAppliedCode) {
                try {
                    $stackableRule = $this->getSalesRuleByCouponCode($alreadyAppliedCode);
                    if ($ruleIdToApply === $stackableRule->getId()) {
                        return false;
                    }
                    $stackableRuleIds = $stackableRule->getStackableRuleIds()
                        ? explode(',', $stackableRule->getStackableRuleIds())
                        : [];
                } catch (NoSuchEntityException) {
                    return false;
                }
            }
            if (!empty($stackableRuleIds)) {
                return isset($couponCode) && in_array($ruleIdToApply, $stackableRuleIds);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get Sales Rule By Coupon Code.
     *
     * @param string $couponCode
     * @return Rule
     * @throws NoSuchEntityException
     */
    public function getSalesRuleByCouponCode(string $couponCode): Rule
    {
        $coupon = $this->couponFactory->create()->loadByCode($couponCode);
        if ($coupon->getRuleId() !== null) {
            return $this->ruleFactory->create()->load($coupon->getRuleId());
        }
        throw new NoSuchEntityException(__("The coupon code isn't valid. Verify the code and try again."));
    }

    /**
     * Check if coupon is stackable or not
     *
     * @param string|null $couponCode
     * @param array $alreadyAppliedCodes
     * @return bool
     */
    public function isCouponStackable(?string $couponCode, array $alreadyAppliedCodes): bool
    {
        $stackableCoupons = $this->getUniqueStackableCoupons()
            ? explode(',', $this->getUniqueStackableCoupons())
            : [];
        $intersect = array_intersect($alreadyAppliedCodes, $stackableCoupons);

        if (!empty($alreadyAppliedCodes)) {
            if (!empty($stackableCoupons)) {
                if (!empty($intersect)) {
                    return isset($couponCode) && in_array($couponCode, $stackableCoupons);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get Unique Codes.
     *
     * @return string
     */
    public function getUniqueStackableCoupons(): string
    {
        $couponCodes = [];
        $salesRules = $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('is_stackable', ['eq' => 1])
            ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
            ->addFieldToFilter('use_auto_generation', ['eq' => 0])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->setOrder('sort_order', 'asc');
        foreach ($salesRules->getData() as $salesRule) {
            $couponCodes[] = $salesRule['code'];
        }
        return empty($couponCodes) ? '' : implode(',', $couponCodes);
    }

    /**
     * Get Current Date
     *
     * @return string
     */
    public function getCurrentDate(): string
    {
        return $this->timezoneInterface->date()->format('Y-m-d');
    }
}
