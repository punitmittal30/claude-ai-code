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

namespace Pratech\CustomDeliveryCharges\Model\CreditMemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Delivery Charges Total Segment to include delivery charges for customer with lower order value.
 */
class DeliveryCharges extends AbstractTotal
{
    /**
     * DeliveryCharges Constructor
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setDeliveryCharges(0);
        $creditmemo->setBaseDeliveryCharges(0);
        $creditmemo->setDeliveryChargesRefunded(0);

        $amount = $creditmemo->getOrder()->getDeliveryCharges();
        $creditmemo->setDeliveryCharges($amount);

        $amount = $creditmemo->getOrder()->getBaseDeliveryCharges();
        $creditmemo->setBaseDeliveryCharges($amount);

        $amount = $creditmemo->getOrder()->getDeliveryChargesRefunded();
        $creditmemo->setDeliveryChargesRefunded($amount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getDeliveryCharges());
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBaseDeliveryCharges());

        return $this;
    }
}
