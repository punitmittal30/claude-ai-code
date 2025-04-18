<?php
/**
 * Pratech_CustomDeliveryCharges
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CustomDeliveryCharges
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CustomDeliveryCharges\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Delivery Charges Total Segment to include delivery charges for customer with lower order value.
 */
class DeliveryCharges extends AbstractTotal
{
    /**
     * Collect Delivery Charges
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setDeliveryCharges(0);
        $invoice->setBaseDeliveryCharges(0);
        $invoice->setDeliveryChargesRefunded(0);

        $amount = $invoice->getOrder()->getDeliveryCharges();
        $invoice->setDeliveryCharges($amount);
        $amount = $invoice->getOrder()->getBaseDeliveryCharges();
        $invoice->setBaseDeliveryCharges($amount);
        $amount = $invoice->getOrder()->getDeliveryChargesRefunded();
        $invoice->setDeliveryChargesRefunded($amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getDeliveryCharges());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getDeliveryCharges());

        return $this;
    }
}
