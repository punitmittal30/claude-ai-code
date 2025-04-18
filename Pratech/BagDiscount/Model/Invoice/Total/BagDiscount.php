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

namespace Pratech\BagDiscount\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Bag Discount Total Segment
 */
class BagDiscount extends AbstractTotal
{
    /**
     * Collect Bag Discount
     *
     * @param  Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setBagDiscount(0);
        $invoice->setBaseBagDiscount(0);

        $amount = $invoice->getOrder()->getBagDiscount();
        $invoice->setBagDiscount($amount);
        $amount = $invoice->getOrder()->getBaseBagDiscount();
        $invoice->setBaseBagDiscount($amount);

        return $this;
    }
}
