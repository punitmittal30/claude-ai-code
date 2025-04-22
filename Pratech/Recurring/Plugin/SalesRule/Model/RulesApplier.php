<?php

namespace Pratech\Recurring\Plugin\SalesRule\Model;

use Exception;
use Magento\Quote\Model\QuoteRepository;

class RulesApplier
{
    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection
     * @param QuoteRepository $quoteRepository
     * @param \Pratech\Recurring\Helper\Recurring $recurringHelper
     */
    public function __construct(
        private \Magento\Checkout\Model\Session $checkoutSession,
        private \Magento\Framework\Json\Helper\Data $jsonHelper,
        private \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection,
        private QuoteRepository $quoteRepository,
        private \Pratech\Recurring\Helper\Recurring $recurringHelper,
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
            $isRecurringEnabled = $this->recurringHelper->isRecurringEnabled()();
            $isDiscountEnabled = $this->recurringHelper-isDiscountEnabled();
            if ($isRecurringEnabled && !$isDiscountEnabled) {
                $quote = null;
                $quoteId = $this->checkoutSession->getQuoteId();
                if ($quoteId) {
                    $quote = $this->quoteRepository->get($quoteId);
                }
                $flag = 0;
                if ($quote && $quote->getId()) {
                    $cartData = $quote->getAllVisibleItems();
                    foreach ($cartData as $item) {
                        $flag = $this->getRuleData($item);
                    }
                }
                if ($flag == 1) {
                    $rules = $this->ruleCollection->create()->addFieldToFilter("rule_id", ["eq" => 0]);
                }
            }
            $result = $proceed($item, $rules, $skipValidation, $couponCode);
        } catch (Exception $e) {
            $this->recurringHelper->logDataInLogger($e->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get rule data function
     *
     * @param array $item
     * @return bool
     */
    public function getRuleData($item)
    {
        $flag = 0;
        if ($customAdditionalOptionsQuote = $item->getOptionByCode('custom_additional_options')) {
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
