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

use Amasty\Coupons\Api\GetCouponsByCartIdInterface;
use Amasty\Coupons\Model\CouponRenderer;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\Quote\ChildrenValidationLocator;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Cart\Model\Config\Source\PlatformUsed;
use Pratech\Cart\Model\Config\Source\RuleType;
use Pratech\Coupon\Model\Coupon\Validator as CouponValidator;

class Coupon
{
    /**
     * @param CouponManagementInterface $couponManagement
     * @param CollectionFactory $salesRuleCollectionFactory
     * @param PlatformUsed $platformUsed
     * @param RuleType $ruleType
     * @param TimezoneInterface $timezoneInterface
     * @param Utility $utility
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CouponRenderer $couponRenderer
     * @param GetCouponsByCartIdInterface $getCouponsByCartId
     * @param CouponValidator $couponValidator
     * @param Logger $apiLogger
     * @param CouponFactory $couponFactory
     * @param RuleFactory $ruleFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param \Pratech\Cart\Helper\Coupon $backwardCompatibilityCoupons
     * @param CalculatorFactory $calculatorFactory
     * @param Utility $validatorUtility
     * @param ChildrenValidationLocator $childrenValidationLocator
     * @param CouponCollectionFactory $couponCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Validator $validator
     */
    public function __construct(
        private CouponManagementInterface   $couponManagement,
        private CollectionFactory           $salesRuleCollectionFactory,
        private PlatformUsed                $platformUsed,
        private RuleType                    $ruleType,
        private TimezoneInterface           $timezoneInterface,
        private Utility                     $utility,
        private CartRepositoryInterface     $quoteRepository,
        private QuoteIdMaskFactory          $quoteIdMaskFactory,
        private CouponRenderer              $couponRenderer,
        private GetCouponsByCartIdInterface $getCouponsByCartId,
        private CouponValidator             $couponValidator,
        private Logger                      $apiLogger,
        private CouponFactory               $couponFactory,
        private RuleFactory                 $ruleFactory,
        private ScopeConfigInterface        $scopeConfig,
        private \Pratech\Cart\Helper\Coupon $backwardCompatibilityCoupons,
        private CalculatorFactory           $calculatorFactory,
        private Utility                     $validatorUtility,
        private ChildrenValidationLocator   $childrenValidationLocator,
        private CouponCollectionFactory     $couponCollectionFactory,
        private StoreManagerInterface       $storeManager,
        private Validator                   $validator
    ) {
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
        if (!$this->isCouponStackingEnabled()) {
            return $this->backwardCompatibilityCoupons->getHeroCouponForCustomer($quoteId, $platform);
        }
        $customerHeroCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->getHeroCouponList($platform);
            try {
                $quote = $this->quoteRepository->get($quoteId);
                $customerHeroCouponList = $this->getListingCoupons($quote, $salesRuleCollection);
            } catch (NoSuchEntityException $e) {
                $this->apiLogger->error(
                    "Customer Hero Coupon | Invalid Quote ID : " . $quoteId . " | "
                    . $e->getMessage() . __METHOD__
                );
            }
        }
        return $customerHeroCouponList;
    }

    /**
     * Is Coupon Stacking Enabled?
     *
     * @return int
     */
    public function isCouponStackingEnabled(): int
    {
        return (int)$this->scopeConfig->getValue('coupon/stacking/enable', ScopeInterface::SCOPE_STORE);
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
            )
            ->addFieldToFilter(
                'from_date',
                [
                    ['lteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )
            ->setOrder('sort_order', 'asc');
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
     * Get Current Date
     *
     * @return string
     */
    public function getCurrentDate(): string
    {
        return $this->timezoneInterface->date()->format('Y-m-d');
    }

    /**
     * Get Listing Coupons.
     *
     * @param Quote $quote
     * @param Collection $salesRuleCollection
     * @return array
     */
    public function getListingCoupons(Quote $quote, Collection $salesRuleCollection): array
    {
        $appliedCoupons = $eligibleCoupon = $ineligibleCoupon = [];
        $alreadyAppliedCoupons = $quote->getCouponCode() ? explode(',', $quote->getCouponCode()) : [];

        /** Looping through the already applied coupon code got from quote object
         * and assigning it to eligible coupon array.
         */
        foreach ($alreadyAppliedCoupons as $alreadyAppliedCoupon) {
            $ruleData['savings'] = 0;
            $ruleId = $this->getRuleIdByCouponCode($alreadyAppliedCoupon);
            $rule = $this->ruleFactory->create()->load($ruleId);
            $ruleData = $rule->getData();
            $ruleData['code'] = $alreadyAppliedCoupon;
            $appliedCoupons[] = $this->getCouponArrayData($ruleData);
        }

        /** @var Rule $salesRule */
        foreach ($salesRuleCollection as $salesRule) {
            $isCustomerGroupValid = $this->validateCustomerGroup(
                $quote->getCustomerIsGuest() ? 0 : $quote->getCustomer()->getGroupId(),
                $salesRule->getCustomerGroupIds()
            );
            if ($isCustomerGroupValid) {
                $quoteAddress = $quote->getShippingAddress();
                $isCouponValid = $this->utility->canProcessRule($salesRule, $quoteAddress);
                if (in_array($salesRule->getCouponCode(), $alreadyAppliedCoupons)) {
                    continue;
                } elseif ($isCouponValid && $salesRule->getStoreCreditPoint() == 0) {
                    $savings = $this->calculatePotentialDiscount($quote, $salesRule);
                    $isCouponStackable = $this->couponValidator->isCouponStackableByRule(
                        $salesRule->getCouponCode(),
                        $alreadyAppliedCoupons
                    );
                    if ($isCouponStackable) {
                        $eligibleCoupon[] = $this->getCouponData($salesRule, $savings);
                    } else {
                        $ineligibleCoupon[] = $this->getCouponData($salesRule, $savings);
                    }
                }
            }
        }
        return [
            'applied' => $appliedCoupons,
            'eligible' => $eligibleCoupon,
            'ineligible' => $ineligibleCoupon
        ];
    }

    /**
     * Get Rule ID by Coupon Code.
     *
     * @param string $couponCode
     * @return int
     */
    public function getRuleIdByCouponCode(string $couponCode): int
    {
        /** @var \Magento\SalesRule\Model\Coupon $coupon */
        $coupon = $this->couponFactory->create();
        $coupon->load($couponCode, 'code');
        return $coupon->getRuleId();
    }

    /**
     * Get Coupon Array Data.
     *
     * @param array $salesRule
     * @return array
     */
    public function getCouponArrayData(array $salesRule): array
    {
        return [
            'coupon' => $salesRule['code'],
            'rule_name' => $salesRule['name'],
            'rule_description' => $salesRule['description'],
            'term_and_conditions' => $salesRule['term_and_conditions'],
            'rule_type' => isset($salesRule['rule_type'])
                ? $this->getRuleTypeLabel($salesRule['rule_type'])
                : '',
            'savings' => $salesRule['savings'] ?? 0,
        ];
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
     * Validate Customer Group.
     *
     * @param int $customerGroupId
     * @param array $salesRuleCustomerGroupIds
     * @return bool
     */
    protected function validateCustomerGroup(int $customerGroupId, array $salesRuleCustomerGroupIds): bool
    {
        if (in_array($customerGroupId, $salesRuleCustomerGroupIds)) {
            return true;
        }
        return false;
    }

    /**
     * Calculate potential discount amount
     *
     * @param Quote $quote
     * @param Rule $salesRule
     * @return float
     */
    private function calculatePotentialDiscount(Quote $quote, Rule $salesRule): float
    {
        $items = $quote->getAllItems();
        $discount = 0;

        if (Rule::CART_FIXED_ACTION === $salesRule->getSimpleAction()) {
            $store = $this->storeManager->getStore($quote->getStoreId());
            $address = $quote->getShippingAddress();
            $this->validator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $salesRule->getCouponCode());
            $this->validator->initTotals($items, $address);
        }

        foreach ($items as $item) {
            if (!$this->canApplyDiscount($item)) {
                continue;
            }
            if (!$salesRule->getActions()->validate($item)) {
                if (!$this->childrenValidationLocator->isChildrenValidationRequired($item)) {
                    continue;
                }
                $childItems = $item->getChildren();
                $isContinue = true;
                if (!empty($childItems)) {
                    foreach ($childItems as $childItem) {
                        if ($salesRule->getActions()->validate($childItem)) {
                            $isContinue = false;
                        }
                    }
                }
                if ($isContinue) {
                    continue;
                }
            }
            try {
                $itemSavings = $this->getDiscountData($item, $salesRule)->getAmount();
            } catch (Exception $e) {
                $itemSavings = 0;
            }
            $discount += $itemSavings;
        }

        return (float)number_format($discount, 2, '.', '');
    }

    /**
     * Check if discount can be applied to item
     *
     * @param AbstractItem $item
     * @return bool
     */
    private function canApplyDiscount($item): bool
    {
        if ($item->getParentItem()) {
            return false;
        }

        if ($item->getNoDiscount()) {
            return false;
        }

        return true;
    }

    /**
     * Get Discount Data for Item.
     *
     * @param AbstractItem $item
     * @param Rule $rule
     * @return Rule\Action\Discount\Data
     */
    protected function getDiscountData(AbstractItem $item, Rule $rule): Rule\Action\Discount\Data
    {
        $qty = $this->validatorUtility->getItemQty($item, $rule);
        $discountCalculator = $this->calculatorFactory->create($rule->getSimpleAction());
        $qty = $discountCalculator->fixQuantity($qty, $rule);
        $item->setDiscountAmount(0);
        return $discountCalculator->calculate($rule, $item, $qty);
    }

    /**
     * Get Coupon Data.
     *
     * @param Rule $salesRule
     * @param float $savings
     * @return array
     */
    public function getCouponData(Rule $salesRule, float $savings): array
    {
        return [
            'coupon' => $salesRule->getCouponCode(),
            'rule_name' => $salesRule->getName(),
            'rule_description' => $salesRule->getDescription(),
            'term_and_conditions' => $salesRule->getTermAndConditions(),
            'rule_type' => $salesRule->getRuleType()
                ? $this->getRuleTypeLabel($salesRule->getRuleType())
                : '',
            'savings' => abs($savings)
        ];
    }

    /**
     * NEW: Get Hero Coupons for Guest
     *
     * @param string $quoteId
     * @param string $platform
     * @return array
     */
    public function getHeroCouponForGuest(string $quoteId, string $platform): array
    {
        $guestHeroCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->getHeroCouponList($platform);
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            try {
                $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                $guestHeroCouponList = $this->getListingCoupons($quote, $salesRuleCollection);
            } catch (NoSuchEntityException $e) {
                return [];
            }
        }
        return $guestHeroCouponList;
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
        if (!$this->isCouponStackingEnabled()) {
            return $this->backwardCompatibilityCoupons->getCouponListingForCustomer($quoteId, $platform);
        }
        $customerCouponList = [];
        if ($platform) {
            $salesRuleCollection = $this->getCouponList($platform);
            try {
                $quote = $this->quoteRepository->get($quoteId);
                $customerCouponList = $this->getListingCoupons($quote, $salesRuleCollection);
            } catch (NoSuchEntityException $e) {
                $this->apiLogger->error(
                    "Customer Listing Coupon | Invalid Quote ID : " . $quoteId . " | "
                    . $e->getMessage() . __METHOD__
                );
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
            )
            ->addFieldToFilter(
                'from_date',
                [
                    ['lteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )
            ->setOrder('sort_order', 'asc');
    }

    /**
     * OLD | NEW: Get Coupon Listing For Guest Customer
     *
     * @param string $quoteId
     * @param string $platform
     * @return array
     */
    public function getCouponListingForGuest(string $quoteId, string $platform): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return $this->backwardCompatibilityCoupons->getGuestCouponListing($platform);
        }

        $guestCouponList = [];
        if ($platform !== 'app') {
            $salesRuleCollection = $this->getCouponList($platform);
            try {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
                $guestCouponList = $this->getListingCoupons($quote, $salesRuleCollection);
            } catch (NoSuchEntityException $e) {
                $this->apiLogger->error(
                    "Guest Listing Coupon | Invalid Quote ID : " . $quoteId . " | "
                    . $e->getMessage() . __METHOD__
                );
            }
        } else {
            $salesRuleCollection = $this->getArrayCouponList($platform);
            if (!empty($salesRuleCollection)) {
                foreach ($salesRuleCollection as $salesRule) {
                    $guestCouponList[] = $this->getCouponArrayData($salesRule);
                }
            }
        }
        return $guestCouponList;
    }

    /**
     * Get Array Coupon List.
     *
     * @param string $platform
     * @return array
     */
    public function getArrayCouponList(string $platform): array
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
            )
            ->addFieldToFilter(
                'from_date',
                [
                    ['lteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )
            ->setOrder('sort_order', 'asc')
            ->getData();
    }

    /**
     * Adds a coupon by code to a specified customer cart.
     *
     * @param int $cartId
     * @param string $couponCode
     * @param string $platform
     * @return array
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function applyCustomerCoupons(int $cartId, string $couponCode, string $platform): array
    {
        if ($this->isCouponStackingEnabled()) {
            $quote = $this->quoteRepository->get($cartId);
            $customerId = $quote->getCustomer()->getId();
            $msg = $this->couponValidator->validateCoupon($quote, $couponCode, $platform, $customerId);
            if (!empty($msg)) {
                throw new NoSuchEntityException(__($msg));
            }
            $alreadyAppliedCustomerCoupons = $this->getCustomerAppliedCoupons($cartId);
            $alreadyAppliedCustomerCoupons[] = $couponCode;
            return $this->applyCoupons($cartId, $alreadyAppliedCustomerCoupons);
        }
        return $this->backwardCompatibilityCoupons->setCustomerCoupon($cartId, $couponCode, $platform);
    }

    /**
     * Returns information of multiple applied coupon in a specified customer cart.
     *
     * @param int $cartId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCustomerAppliedCoupons(int $cartId): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return [
                "coupon_code" => $this->backwardCompatibilityCoupons->getCustomerCoupon($cartId)
            ];
        }
        $quote = $this->quoteRepository->get($cartId);
        if (!$quote->getCouponCode()) {
            return [];
        }
        return $this->couponRenderer->parseCoupon($quote->getCouponCode());
    }

    /**
     * Adds coupon codes to a specified cart.
     *
     * @param int $cartId
     * @param array $couponCodes
     * @return array
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function applyCoupons(int $cartId, array $couponCodes): array
    {
        $couponCodes = $this->filterCoupons($couponCodes);
        $quote = $this->quoteRepository->getActive($cartId);
        try {
            $this->couponManagement->set($cartId, implode(',', $couponCodes));
        } catch (NoSuchEntityException $exception) {
            if (!$quote->getItemsCount() || !$quote->getStoreId()) {
                throw $exception;
            }
        }
        $appliedCodes = $this->couponRenderer->render($quote->getCouponCode());
        $result = [];
        foreach ($couponCodes as $code) {
            $couponKey = $this->couponRenderer->findCouponInArray($code, $appliedCodes);
            if ($couponKey !== false) {
                $code = $appliedCodes[$couponKey];
                $result[] = $code;
            }
        }
        return $result;
    }

    /**
     * Filter Coupons.
     *
     * @param array $couponCodes
     * @return array
     */
    private function filterCoupons(array $couponCodes): array
    {
        $inputCoupons = [];
        foreach ($couponCodes as $code) {
            if ($this->couponRenderer->findCouponInArray($code, $inputCoupons) === false) {
                $inputCoupons[] = $code;
            }
        }
        return $inputCoupons;
    }

    /**
     * Remove a coupon from a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @param string $couponCode
     * @return array
     * @throws NoSuchEntityException The specified cart does not exist.
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     */
    public function removeCustomerCoupons(int $cartId, string $couponCode): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return [
                "removed" => $this->backwardCompatibilityCoupons->removeCustomerCoupon($cartId)
            ];
        }
        $alreadyAppliedCustomerCoupons = $this->getCustomerAppliedCoupons($cartId);
        if (($key = array_search($couponCode, $alreadyAppliedCustomerCoupons)) !== false) {
            unset($alreadyAppliedCustomerCoupons[$key]);
        }
        return $this->applyCoupons($cartId, $alreadyAppliedCustomerCoupons);
    }

    /**
     * Remove a coupon from a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @param string $couponCode
     * @return array
     * @throws NoSuchEntityException The specified cart does not exist.
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     */
    public function removeGuestCoupons(string $cartId, string $couponCode): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return [
                "removed" => $this->backwardCompatibilityCoupons->removeGuestCoupon($cartId)
            ];
        }
        $alreadyAppliedGuestCoupons = $this->getGuestAppliedCoupons($cartId);
        if (($key = array_search($couponCode, $alreadyAppliedGuestCoupons)) !== false) {
            unset($alreadyAppliedGuestCoupons[$key]);
        }
        $quoteId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
        return $this->applyCoupons($quoteId, $alreadyAppliedGuestCoupons);
    }

    /**
     * Return applied coupon information for a specified guest cart.
     *
     * @param string $cartId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGuestAppliedCoupons(string $cartId): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return [
                "coupon_code" => $this->backwardCompatibilityCoupons->getGuestCoupon($cartId)
            ];
        }
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->getCouponsByCartId->get($quoteIdMask->getQuoteId());
    }

    /**
     * Apply multiple coupons by code to a specified guest cart.
     *
     * @param string $cartId
     * @param string $couponCode
     * @param string $platform
     * @return array
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function applyGuestCoupons(string $cartId, string $couponCode, string $platform): array
    {
        if (!$this->isCouponStackingEnabled()) {
            return $this->backwardCompatibilityCoupons->setGuestCoupon($cartId, $couponCode, $platform);
        }
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        $msg = $this->couponValidator->validateCoupon($quote, $couponCode, $platform);
        if (!empty($msg)) {
            throw new NoSuchEntityException(__($msg));
        }
        $alreadyAppliedGuestCoupons = $this->getGuestAppliedCoupons($cartId);
        $alreadyAppliedGuestCoupons[] = $couponCode;

        return $this->applyCoupons($quote->getId(), $alreadyAppliedGuestCoupons);
    }

    /**
     * Get Coupons By RuleId
     *
     * @param int $ruleId
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function getCouponsByRuleId(int $ruleId, int $pageSize = 10, int $currentPage = 1): array
    {
        $collection = $this->couponCollectionFactory->create();
        $collection->addFieldToFilter('rule_id', $ruleId)
            ->setPageSize($pageSize)
            ->setCurPage($currentPage)
            ->addFieldToSelect('code');

        $couponCodes = [];
        foreach ($collection as $coupon) {
            $couponCodes[] = $coupon->getCode();
        }

        return [
            'rule_id' => $ruleId,
            'page_size' => $pageSize,
            'current_page' => $currentPage,
            'total_count' => $collection->getSize(),
            'coupon_codes' => $couponCodes
        ];
    }
}
