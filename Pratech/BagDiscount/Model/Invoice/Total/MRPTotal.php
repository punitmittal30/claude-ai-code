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
 * MRP Total Total Segment
 */
class MRPTotal extends AbstractTotal
{
    /**
     * Collect MrpTotal
     *
     * @param  Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setMrpTotal(0);
        $invoice->setBaseMrpTotal(0);

        $amount = $invoice->getOrder()->getMrpTotal();
        $invoice->setMrpTotal($amount);
        $amount = $invoice->getOrder()->getBaseMrpTotal();
        $invoice->setBaseMrpTotal($amount);

        return $this;
    }
}
