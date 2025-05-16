<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Helper;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Quote\Api\GuestCouponManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Utility;
use Pratech\Base\Logger\Logger;
use Pratech\Cart\Model\Config\Source\PlatformUsed;
use Pratech\Cart\Model\Config\Source\RuleType;

/**
 * Coupon Helper Class
 */
class Coupon
{
    /**
     * Coupon Helper Class Constructor
     *
     * @param CouponManagementInterface $couponManagement
     * @param GuestCouponManagementInterface $guestCouponManagement
     * @param CollectionFactory $salesRuleCollectionFactory
     * @param PlatformUsed $platformUsed
     * @param RuleType $ruleType
     * @param TimezoneInterface $timezoneInterface
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param GuestCartTotalRepositoryInterface $guestCartTotalRepository
     * @param RuleFactory $ruleFactory
     * @param CouponFactory $couponFactory
     * @param Utility $utility
     * @param CartRepositoryInterface $quoteRepository
     * @param Logger $apiLogger
     */
    public function __construct(
        private CouponManagementInterface         $couponManagement,
        private GuestCouponManagementInterface    $guestCouponManagement,
        private CollectionFactory                 $salesRuleCollectionFactory,
        private PlatformUsed                      $platformUsed,
        private RuleType                          $ruleType,
        private TimezoneInterface                 $timezoneInterface,
        private CartTotalRepositoryInterface      $cartTotalRepository,
        private GuestCartTotalRepositoryInterface $guestCartTotalRepository,
        private RuleFactory                       $ruleFactory,
        private CouponFactory                     $couponFactory,
        private Utility                           $utility,
        private CartRepositoryInterface           $quoteRepository,
        private Logger                            $apiLogger
    ) {
    }

    /**
     * Get Customer Coupon Details
     *
     * @param int $cartId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getCustomerCoupon(int $cartId): ?string
    {
        return $this->couponManagement->get($cartId);
    }

    /**
     * Apply Customer Coupon
     *
     * @param int $cartId
     * @param string $couponCode
     * @param string|null $platform
     * @return array
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function setCustomerCoupon(int $cartId, string $couponCode, string $platform = null): array
    {
        $salesRule = $this->getSalesRuleByCouponCode($couponCode);
        if ($this->validateCouponPlatform($salesRule, $platform)) {
            $isCouponApplied = $this->couponManagement->set($cartId, $couponCode);
            $totals = $this->cartTotalRepository->get($cartId);
            $discountAmount = $totals->getDiscountAmount();

            if ($isCouponApplied) {
                return [
                    "applied" => true,
                    "discount_amount" => $discountAmount,
                    "type" => $salesRule->getSimpleAction()
                ];
            }
        }
        throw new NoSuchEntityException(__("The coupon code isn't valid. Verify the code and try again."));
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
     * Validate Coupon Platform
     *
     * @param Rule $salesRule
     * @param string|null $platform
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function validateCouponPlatform(Rule $salesRule, string $platform = null): bool
    {
        $platformApplicable = $salesRule->getPlatformApplicable();
        if (in_array($platformApplicable, [$this->getPlatformValue($platform), 3])) {
            return true;
        } else {
            throw new NoSuchEntityException(
                __("The coupon code is valid only for " . $this->getPlatformLabel($platformApplicable) . ".")
            );
        }
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
     * Get Platform Used
     *
     * @param int $platform
     * @return string|mixed
     */
    private function getPlatformLabel(int $platform): mixed
    {
        if ($platform) {
            foreach ($this->platformUsed->toOptionArray() as $option) {
                if ($option['value'] == $platform) {
                    return $option['label'];
                }
            }
        }
        return "";
    }

    /**
     * Remove Customer Coupon
     *
     * @param int $cartId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function removeCustomerCoupon(int $cartId): bool
    {
        return $this->couponManagement->remove($cartId);
    }

    /**
     * Get Guest Coupon Details
     *
     * @param string $cartId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getGuestCoupon(string $cartId): ?string
    {
        return $this->guestCouponManagement->get($cartId);
    }

    /**
     * Apply Guest Coupon
     *
     * @param string $cartId
     * @param string $couponCode
     * @param string|null $platform
     * @return array
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function setGuestCoupon(string $cartId, string $couponCode, string $platform = null): array
    {
        $salesRule = $this->getSalesRuleByCouponCode($couponCode);
        if ($this->validateCouponPlatform($salesRule, $platform)) {
            $isCouponApplied = $this->guestCouponManagement->set($cartId, $couponCode);
            $guestTotals = $this->guestCartTotalRepository->get($cartId);
            $discountAmount = $guestTotals->getDiscountAmount();

            if ($isCouponApplied) {
                return [
                    "applied" => true,
                    "discount_amount" => $discountAmount,
                    "type" => $salesRule->getSimpleAction()
                ];
            }
        }
        throw new NoSuchEntityException(__("The coupon code isn't valid. Verify the code and try again."));
    }

    /**
     * Remove Guest Coupon
     *
     * @param string $cartId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function removeGuestCoupon(string $cartId): bool
    {
        return $this->guestCouponManagement->remove($cartId);
    }

    /**
     * OLD: Get Coupon Listing For Guest Customer
     *
     * @param string $platform
     * @return array
     */
    public function getGuestCouponListing(string $platform): array
    {
        $couponList = [];
        if ($platform) {
            $salesRuleCollection = $this->salesRuleCollectionFactory->create()
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
                ->addFieldToFilter('show_in_coupon_listing', ['eq' => 1])
                ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
                ->addFieldToFilter('use_auto_generation', ['eq' => 0])
                ->addFieldToFilter(
                    'to_date',
                    [
                        ['gteq' => $this->getCurrentDate()],
                        ['null' => true]
                    ]
                )
                ->getData();
            if (!empty($salesRuleCollection)) {
                foreach ($salesRuleCollection as $salesRule) {
                    $couponList[] = [
                        'coupon' => $salesRule['code'],
                        'rule_name' => $salesRule['name'],
                        'rule_description' => $salesRule['description'],
                        'term_and_conditions' => $salesRule['term_and_conditions'],
                        'rule_type' => $salesRule['rule_type'] ? $this->getRuleTypeLabel($salesRule['rule_type']) : ''
                    ];
                }
            }
        }
        return $couponList;
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

    /**
     * Get Rule Type Label
     *
     * @param int $ruleType
     * @return string
     */
    private function getRuleTypeLabel(int $ruleType): mixed
    {
        if ($ruleType) {
            foreach ($this->ruleType->toOptionArray() as $option) {
                if ($option['value'] == $ruleType) {
                    return $option['label'];
                }
            }
        }
        return "";
    }

    /**
     * OLD: Get Coupon Listing For Customer
     *
     * @param string $platform
     * @return array
     */
    public function getCustomerCouponListing(string $platform): array
    {
        $couponList = [];
        if ($platform) {
            $salesRuleCollection = $this->salesRuleCollectionFactory->create()
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
                ->addFieldToFilter('show_in_coupon_listing', ['eq' => 1])
                ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
                ->addFieldToFilter('use_auto_generation', ['eq' => 0])
                ->addFieldToFilter(
                    'to_date',
                    [
                        ['gteq' => $this->getCurrentDate()],
                        ['null' => true]
                    ]
                );
            if (!empty($salesRuleCollection)) {
                foreach ($salesRuleCollection->getData() as $salesRule) {
                    $couponList[] = [
                        'coupon' => $salesRule['code'],
                        'rule_name' => $salesRule['name'],
                        'rule_description' => $salesRule['description'],
                        'term_and_conditions' => $salesRule['term_and_conditions']
                    ];
                }
            }
        }
        return $couponList;
    }

    /**
     * OLD: Get Hero Coupons
     *
     * @param string|null $platform
     * @return array
     */
    public function getHeroCoupons(string $platform = null): array
    {
        $heroCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->salesRuleCollectionFactory->create()
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
                ->addFieldToFilter('hero_coupon', ['eq' => 1])
                ->addFieldToFilter('coupon_type', ['eq' => 2])
                ->addFieldToFilter('use_auto_generation', ['eq' => 0])
                ->addFieldToFilter(
                    'to_date',
                    [
                        ['gteq' => $this->getCurrentDate()],
                        ['null' => true]
                    ]
                )->setOrder('sort_order', 'asc');
            if (!empty($salesRuleCollection)) {
                foreach ($salesRuleCollection->getData() as $salesRule) {
                    $heroCouponList[] = [
                        'coupon' => $salesRule['code'],
                        'rule_name' => $salesRule['name'],
                        'rule_description' => $salesRule['description'],
                        'term_and_conditions' => $salesRule['term_and_conditions']
                    ];
                }
            }
        }
        return $heroCouponList;
    }

    /**
     * Get Rule Data By ID
     *
     * @param array $ruleIds
     * @param array $couponTypes
     * @return array
     */
    public function getRuleDetails(array $ruleIds, array $couponTypes): array
    {
        $ruleData = [];
        $salesRuleCollection = $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => [$ruleIds]])
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )
            ->addFieldToFilter('coupon_type', ['in' => [$couponTypes]]);

        if (!empty($salesRuleCollection)) {
            foreach ($salesRuleCollection->getData() as $salesRule) {
                if ($salesRule['store_credit_point'] && $salesRule['store_credit_point'] > 0) {
                    continue;
                }
                $ruleData[] = [
                    'rule_id' => $salesRule['rule_id'],
                    'rule_name' => $salesRule['name'],
                    'coupon' => $salesRule['code'],
                    'rule_description' => $salesRule['description'],
                    'term_and_conditions' => $salesRule['term_and_conditions']
                ];
            }
        }
        return $ruleData;
    }

    /**
     * NEW: Get Coupon Listing For Customer
     *
     * @param int $quoteId
     * @param string $platform
     * @return array
     */
    public function getCouponListingForCustomer(int $quoteId, string $platform): array
    {
        $customerCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->getCouponList($platform);
            try {
                $quote = $this->quoteRepository->get($quoteId);
                foreach ($salesRuleCollection as $salesRule) {
                    if (in_array($quote->getCustomer()->getGroupId(), $salesRule->getCustomerGroupIds())) {
                        if (!empty($eligibleCoupon = $this->getEligibleCoupons($quote, $salesRule))) {
                            $customerCouponList[] = $eligibleCoupon;
                        }
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->apiLogger->error("Customer Listing Coupon | Invalid Quote ID : " . $quoteId . " | "
                    . $e->getMessage() . __METHOD__);
            }
        }
        return $customerCouponList;
    }

    /**
     * Get Coupon List.
     *
     * @param string $platform
     * @return Collection
     */
    public function getCouponList(string $platform): Collection
    {
        return $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
            ->addFieldToFilter('show_in_coupon_listing', ['eq' => 1])
            ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
            ->addFieldToFilter('use_auto_generation', ['eq' => 0])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->setOrder('sort_order', 'asc');
    }

    /**
     * Get Eligible Coupons.
     *
     * @param Quote $quote
     * @param Rule $salesRule
     * @return array
     */
    public function getEligibleCoupons(Quote $quote, Rule $salesRule): array
    {
        $couponList = [];
        $quoteAddress = $quote->getShippingAddress();
        $isCouponValid = $this->utility->canProcessRule($salesRule, $quoteAddress);
        if ($isCouponValid && $salesRule->getStoreCreditPoint() == 0) {
            try {
                $couponList = [
                    'coupon' => $salesRule->getCouponCode(),
                    'rule_name' => $salesRule->getName(),
                    'rule_description' => $salesRule->getDescription(),
                    'term_and_conditions' => $salesRule->getTermAndConditions(),
                    'rule_type' => $salesRule->getRuleType()
                        ? $this->getRuleTypeLabel($salesRule->getRuleType())
                        : ''
                ];
            } catch (Exception $e) {
                $this->apiLogger->error(
                    "Error calculating savings for coupon: " . $salesRule->getCouponCode() .
                    " | " . $e->getMessage() .
                    " | " . __METHOD__
                );
            }
        }
        return $couponList;
    }

    /**
     * NEW: Get Hero Coupons
     *
     * @param int $quoteId
     * @param string $platform
     * @return array
     */
    public function getHeroCouponForCustomer(int $quoteId, string $platform): array
    {
        $customerHeroCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->getHeroCouponList($platform);
            try {
                $quote = $this->quoteRepository->get($quoteId);
                foreach ($salesRuleCollection as $salesRule) {
                    if (in_array($quote->getCustomer()->getGroupId(), $salesRule->getCustomerGroupIds())) {
                        if (!empty($eligibleCoupon = $this->getEligibleCoupons($quote, $salesRule))) {
                            $customerHeroCouponList[] = $eligibleCoupon;
                        }
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->apiLogger->error("Customer Hero Coupon | Invalid Quote ID : " . $quoteId . " | "
                    . $e->getMessage() . __METHOD__);
            }
        }
        return $customerHeroCouponList;
    }

    /**
     * Get Coupon List.
     *
     * @param string $platform
     * @return Collection
     */
    public function getHeroCouponList(string $platform): Collection
    {
        return $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
            ->addFieldToFilter('hero_coupon', ['eq' => 1])
            ->addFieldToFilter('show_in_coupon_listing', ['eq' => 1])
            ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
            ->addFieldToFilter('use_auto_generation', ['eq' => 0])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->setOrder('sort_order', 'asc');
    }
}
