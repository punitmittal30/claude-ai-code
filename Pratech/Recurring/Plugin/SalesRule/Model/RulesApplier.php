<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Plugin\SalesRule\Model;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Quote\Model\QuoteRepository;
use Pratech\Recurring\Helper\Recurring as RecurringHelper;

class RulesApplier
{
    /**
     * @param Session $checkoutSession
     * @param JsonHelper $jsonHelper
     * @param CollectionFactory $ruleCollection
     * @param QuoteRepository $quoteRepository
     * @param RecurringHelper $recurringHelper
     */
    public function __construct(
        private Session $checkoutSession,
        private JsonHelper $jsonHelper,
        private CollectionFactory $ruleCollection,
        private QuoteRepository $quoteRepository,
        private RecurringHelper $recurringHelper
    ) {
    }

    /**
     * Plugin for applyRules function
     *
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $rules
     * @param bool $skipValidation
     * @param mixed $couponCode
     * @return array
     */
    public function aroundApplyRules(
        \Magento\SalesRule\Model\RulesApplier $subject,
        \Closure $proceed,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    ) {
        try {
            $isRecurringEnabled = $this->recurringHelper->isRecurringEnabled();
            $isDiscountEnabled = $this->recurringHelper->isDiscountEnabled();
            $isCashbackEnabled = $this->recurringHelper->isCashbackEnabled();
            if ($isDiscountEnabled && $isCashbackEnabled) {
                $rules = $rules;
            } else {
                $quote = $item->getQuote();
                $flag = 0;
                if ($quote && $quote->getId()) {
                    $cartData = $quote->getAllVisibleItems();
                    foreach ($cartData as $cartItem) {
                        $flag = $this->getRuleData($cartItem);
                    }
                }
                if ($flag == 1) {
                    $cashbackRuleIds = [];
                    foreach ($rules as $rule) {
                        if ($rule->getStoreCreditPoint() > 0) {
                            $cashbackRuleIds[] = $rule->getId();
                        }
                    }
                    if ($isCashbackEnabled && !empty($cashbackRuleIds)) {
                        $rules = $rules;
                    } elseif ($isDiscountEnabled && empty($cashbackRuleIds)) {
                        $rules = $rules;
                    } else {
                        $rules = $this->ruleCollection->create()->addFieldToFilter("rule_id", ["eq" => 0]);
                    }
                }
            }
        } catch (Exception $e) {
            $this->recurringHelper->logErrorInLogger($e->getMessage() . __METHOD__);
        }
        $result = $proceed($item, $rules, $skipValidation, $couponCode);
        return $result;
    }

    /**
     * Get rule data function
     *
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     * @return bool
     */
    public function getRuleData($cartItem)
    {
        $flag = 0;
        if ($customAdditionalOptionsQuote = $cartItem->getOptionByCode('custom_additional_options')) {
            $allOptions = $this->jsonHelper->jsonDecode(
                $customAdditionalOptionsQuote->getValue()
            );
            foreach ($allOptions as $allOption) {
                $flag = 1;
            }
        }
        return $flag;
    }
}
