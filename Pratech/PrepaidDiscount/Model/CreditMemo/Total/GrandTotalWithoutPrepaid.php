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
 * Grand Total Without Prepaid Total Segment
 */
class GrandTotalWithoutPrepaid extends AbstractTotal
{
    /**
     * Grand Total Without Prepaid Constructor
     *
     * @param  \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setGrandTotalWithoutPrepaid(0);
        $creditmemo->setBaseGrandTotalWithoutPrepaid(0);

        $amount = $creditmemo->getOrder()->getGrandTotalWithoutPrepaid();
        $creditmemo->setGrandTotalWithoutPrepaid($amount);

        $amount = $creditmemo->getOrder()->getBaseGrandTotalWithoutPrepaid();
        $creditmemo->setBaseGrandTotalWithoutPrepaid($amount);

        return $this;
    }
}
