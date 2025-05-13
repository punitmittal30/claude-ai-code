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

namespace Pratech\PrepaidDiscount\Model\CreditMemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Prepaid Discount Total Segment
 */
class PrepaidDiscount extends AbstractTotal
{
    /**
     * Prepaid Discount Constructor
     *
     * @param  \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setPrepaidDiscount(0);
        $creditmemo->setBasePrepaidDiscount(0);

        $amount = $creditmemo->getOrder()->getPrepaidDiscount();
        $creditmemo->setPrepaidDiscount($amount);

        $amount = $creditmemo->getOrder()->getBasePrepaidDiscount();
        $creditmemo->setBasePrepaidDiscount($amount);

        return $this;
    }
}
