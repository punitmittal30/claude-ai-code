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

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Customer;
use Magento\SalesRule\Model\Rule\CustomerFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Utility;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Coupon\Helper\Data as ConfigData;

class CouponValidator extends AbstractHelper
{
    /**
     * @var array
     */
    protected array $conditions = [];

    /**
     * @var Rule $_rule
     */
    protected $_rule;

    /**
     * Coupon Validator Constructor.
     *
     * @param Context $context
     * @param CouponFactory $couponFactory
     * @param Data $configData
     * @param TimezoneInterface $date
     * @param RuleFactory $ruleFactory
     * @param StoreManagerInterface $storeManager
     * @param UsageFactory $usage
     * @param DataObjectFactory $objectFactory
     * @param CustomerFactory $customerFactory
     * @param Utility $utility
     * @param Cart $cart
     */
    public function __construct(
        Context                       $context,
        private CouponFactory         $couponFactory,
        private ConfigData            $configData,
        private TimezoneInterface     $date,
        private RuleFactory           $ruleFactory,
        private StoreManagerInterface $storeManager,
        private UsageFactory          $usage,
        private DataObjectFactory     $objectFactory,
        private CustomerFactory       $customerFactory,
        private Utility               $utility,
        private Cart                  $cart,
    ) {
        parent::__construct($context);
    }

    /**
     * Validate
     *
     * @param string $couponCode
     * @param int|null $customerId
     * @return array|string|string[]
     * @throws NoSuchEntityException
     */
    public function validate(string $couponCode, ?int $customerId): array|string
    {
        $msg = "";
        $coupon = $this->couponFactory->create();
        $coupon->load($couponCode, 'code');

        // Check if Coupon exists or not
        if (empty($coupon->getData())) {
            $msg = $this->configData->isCouponExits();
            return str_replace("%s", $couponCode, $msg);
        } else {
            // Check for coupon status
            $rule = $this->getRule($coupon->getRuleId());
            if (!$rule->getIsActive()) {
                $msg = $this->configData->isCouponExits();
                return str_replace("%s", $couponCode, $msg);
            }

            // Check for Coupon Expiry
            $couponExpiry = $this->checkExpiry($coupon->getRuleId());
            if ($couponExpiry) {
                $msg = $this->configData->isCouponExpired();
                return str_replace("%s", $couponCode, $msg);
            }

            //validation for customer group
            $isCouponApplicableForGuest = $this->validateIsValidForGuest($coupon->getruleId(), $customerId);
            if (!$isCouponApplicableForGuest) {
                $msg = $this->configData->isCouponCustomerGroup();
                return str_replace("%s", $couponCode, $msg);
            }

            // Validation for Website
            $couponWebsite = $this->validateCurrentWebsite($coupon->getruleId());
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
            $couponCondition = $this->validateCondition($coupon);
            if ($couponCondition) {
                $msg = $this->configData->isConditionFail();
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
     * Check Coupon Expiry.
     *
     * @param int $ruleId
     * @return bool
     */
    protected function checkExpiry(int $ruleId): bool
    {
        $couponCodeData = $this->getRule($ruleId);
        $couponDate = $couponCodeData->getToDate();
        $now = $this->date->date()->format('Y-m-d');
        if (!(empty($couponDate)) && strtotime($couponDate) < strtotime($now)) {
            return true;
        }
        return false;
    }

    /**
     * Check if coupon is assigned to current customer group
     *
     * @param int $ruleId
     * @param int|null $customerId
     * @return bool
     */
    protected function validateIsValidForGuest(int $ruleId, ?int $customerId): bool
    {
        $guestCustomerGroup = 0;
        $couponCodeData = $this->getRule($ruleId);

        if ($customerId == null) {
            if (in_array($guestCustomerGroup, $couponCodeData->getCustomerGroupIds())) {
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
     * @param int $ruleId
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function validateCurrentWebsite(int $ruleId): bool
    {
        $currentWebsite = $this->storeManager->getStore()->getWebsiteId();
        $couponCodeData = $this->getRule($ruleId);
        if (!in_array($currentWebsite, $couponCodeData->getWebsiteIds())) {
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
     * OLD: Validate Coupon Condition.
     *
     * @param Coupon $coupon
     * @return bool
     */
    protected function validateCondition(Coupon $coupon): bool
    {
        $rule = $this->getRule($coupon->getRuleId());
        $quote = $this->getQuote();
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
                }
            }

            if (!$validAction) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Quote.
     *
     * @return Quote
     */
    protected function getQuote(): Quote
    {
        return $this->cart->getQuote();
    }
}
