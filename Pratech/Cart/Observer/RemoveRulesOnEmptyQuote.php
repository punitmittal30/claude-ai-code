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

namespace Pratech\Cart\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

class RemoveRulesOnEmptyQuote implements ObserverInterface
{

    /**
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        private QuoteFactory $quoteFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $quote = $quoteItem->getQuote();
        if ($quote->getItemsCount() === 0 ||
            ($quote->getItemsCount() == 1 && $quoteItem->getPrice() == 0)
        ) {
            $this->removeCartPriceRules($quote);
        }
    }

    /**
     * Remove Cart Price Rules
     *
     * @param Quote $quote
     * @return void
     */
    private function removeCartPriceRules($quote)
    {
        $appliedRuleIds = $quote->getAppliedRuleIds();
        if (!empty($appliedRuleIds)) {
            $quote->setAppliedRuleIds('');
            $quote->setCouponCode('');
            $this->quoteFactory->create()->loadByIdWithoutStore($quote->getId())->save();
        }
    }
}
