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

namespace Pratech\PrepaidDiscount\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Prepaid Discount Total Segment
 */
class PrepaidDiscount extends AbstractTotal
{
    /**
     * Collect Prepaid Discount
     *
     * @param  Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setPrepaidDiscount(0);
        $invoice->setBasePrepaidDiscount(0);

        $amount = $invoice->getOrder()->getPrepaidDiscount();
        $invoice->setPrepaidDiscount($amount);
        $amount = $invoice->getOrder()->getBasePrepaidDiscount();
        $invoice->setBasePrepaidDiscount($amount);

        return $this;
    }
}
