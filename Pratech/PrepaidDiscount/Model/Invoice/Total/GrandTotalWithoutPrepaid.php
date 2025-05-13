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
 * Grand Total Without Prepaid Total Segment
 */
class GrandTotalWithoutPrepaid extends AbstractTotal
{
    /**
     * Collect Grand Total Without Prepaid
     *
     * @param  Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setGrandTotalWithoutPrepaid(0);
        $invoice->setBaseGrandTotalWithoutPrepaid(0);

        $amount = $invoice->getOrder()->getGrandTotalWithoutPrepaid();
        $invoice->setGrandTotalWithoutPrepaid($amount);
        $amount = $invoice->getOrder()->getBaseGrandTotalWithoutPrepaid();
        $invoice->setBaseGrandTotalWithoutPrepaid($amount);

        return $this;
    }
}
