<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Plugin\Cart;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Pratech\Cart\Api\Data\AppliedRuleInterface;
use Pratech\Cart\Helper\Coupon as CouponHelper;
use Pratech\Cart\Model\AppliedRuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

class CartTotalRepository
{

    /**
     * Cart Total Repository Constructor
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param CouponHelper $couponHelper
     * @param AppliedRuleFactory $appliedRuleFactory
     * @param CollectionFactory $salesRuleCollectionFactory
     */
    public function __construct(
        private CartRepositoryInterface $quoteRepository,
        private CouponHelper            $couponHelper,
        private AppliedRuleFactory      $appliedRuleFactory,
        private CollectionFactory       $salesRuleCollectionFactory,
    ) {
    }

    /**
     * After Get Method.
     *
     * @param \Magento\Quote\Model\Cart\CartTotalRepository $subject
     * @param TotalsInterface $quoteTotals
     * @param int $cartId
     * @return TotalsInterface
     * @throws NoSuchEntityException
     */
    public function afterGet(
        \Magento\Quote\Model\Cart\CartTotalRepository $subject,
        TotalsInterface                               $quoteTotals,
        int                                           $cartId
    ): TotalsInterface {
        $extensionAttributes = $quoteTotals->getExtensionAttributes();
        $extensionAttributes->setAppliedRule($this->getAppliedRuleData($cartId));
        $extensionAttributes->setStoreCreditRule($this->getStoreCreditRuleData($cartId));
        $quoteTotals->setExtensionAttributes($extensionAttributes);
        return $quoteTotals;
    }

    /**
     * Get Applied Rule Data
     *
     * @param int $cartId
     * @return AppliedRuleInterface[]
     * @throws NoSuchEntityException
     */
    protected function getAppliedRuleData(int $cartId): array
    {
        /** @var AppliedRuleInterface[] $appliedRuleData */
        $appliedRuleData = [];
        $quote = $this->quoteRepository->getActive($cartId);
        $appliedRuleIds = $quote->getAppliedRuleIds();

        if (!empty($appliedRuleIds)) {
            $rulesData = $this->couponHelper->getRuleDetails(explode(',', $appliedRuleIds), [1, 2]);
            foreach ($rulesData as $rule) {
                $appliedRule = $this->appliedRuleFactory->create()
                    ->setRuleId($rule['rule_id'])
                    ->setRuleName($rule['rule_name'])
                    ->setCoupon($rule['coupon'])
                    ->setRuleDescription($rule['rule_description'])
                    ->setTermAndConditions($rule['term_and_conditions']);
                $appliedRuleData[] = $appliedRule;
            }
        }
        return $appliedRuleData;
    }

    /**
     * Get Rule Data By Id
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
                    ['gteq' => $this->couponHelper->getCurrentDate()],
                    ['null' => true]
                ]
            )
            ->addFieldToFilter('coupon_type', ['in' => [$couponTypes]]);

        if (!empty($salesRuleCollection)) {
            foreach ($salesRuleCollection->getData() as $salesRule) {
                if ($salesRule['store_credit_point'] && $salesRule['store_credit_point'] > 0) {
                    $ruleData[] = [
                        'rule_id' => $salesRule['rule_id'],
                        'rule_name' => $salesRule['name'],
                        'coupon' => $salesRule['code'],
                        'rule_description' => $salesRule['description'],
                        'term_and_conditions' => $salesRule['term_and_conditions']
                    ];
                }
            }
        }
        return $ruleData;
    }

    /**
     * Get Applied Rule Data
     *
     * @param int $cartId
     * @return AppliedRuleInterface[]
     * @throws NoSuchEntityException
     */
    protected function getStoreCreditRuleData(int $cartId): array
    {
        /** @var AppliedRuleInterface[] $storeCredtiRuleData */
        $storeCreditRuleData = [];
        $quote = $this->quoteRepository->getActive($cartId);
        $appliedRuleIds = $quote->getAppliedRuleIds();

        if (!empty($appliedRuleIds)) {
            $rulesData = $this->getRuleDetails(explode(',', $appliedRuleIds), [1, 2]);
            foreach ($rulesData as $rule) {
                $appliedRule = $this->appliedRuleFactory->create()
                    ->setRuleId($rule['rule_id'])
                    ->setRuleName($rule['rule_name'])
                    ->setCoupon($rule['coupon'])
                    ->setRuleDescription($rule['rule_description'])
                    ->setTermAndConditions($rule['term_and_conditions']);
                $storeCreditRuleData[] = $appliedRule;
            }
        }
        return $storeCreditRuleData;
    }
}
