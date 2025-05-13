<?php
/**
 * Pratech_PrepaidDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\PrepaidDiscount
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\PrepaidDiscount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddPrepaidDiscountToOrder implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();

        $prepaidDiscount = $quote->getPrepaidDiscount();
        $basePrepaidDiscount = $quote->getBasePrepaidDiscount();

        $grandTotalWithoutPrepaid = $quote->getGrandTotalWithoutPrepaid();
        $baseGrandTotalWithoutPrepaid = $quote->getBaseGrandTotalWithoutPrepaid();

        $order = $observer->getOrder();

        if ($prepaidDiscount && $basePrepaidDiscount) {
            $order->setData('prepaid_discount', $prepaidDiscount);
            $order->setData('base_prepaid_discount', $basePrepaidDiscount);
        }

        if ($grandTotalWithoutPrepaid && $baseGrandTotalWithoutPrepaid) {
            $order->setData('grand_total_without_prepaid', $grandTotalWithoutPrepaid);
            $order->setData('base_grand_total_without_prepaid', $baseGrandTotalWithoutPrepaid);
        }

        return $this;
    }
}
