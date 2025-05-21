<?php
/**
 * Pratech_BagDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\BagDiscount
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\BagDiscount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddBagDiscountAndMRPTotalToOrder implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();

        $bagDiscount = $quote->getBagDiscount();
        $baseBagDiscount = $quote->getBaseBagDiscount();

        $mrpTotal = $quote->getMrpTotal();
        $baseMrpTotal = $quote->getBaseMrpTotal();

        $order = $observer->getOrder();
        if ($bagDiscount && $baseBagDiscount) {
            $order->setData('bag_discount', $bagDiscount);
            $order->setData('base_bag_discount', $baseBagDiscount);
        }

        if ($mrpTotal && $baseMrpTotal) {
            $order->setData('mrp_total', $mrpTotal);
            $order->setData('base_mrp_total', $baseMrpTotal);
        }

        return $this;
    }
}
