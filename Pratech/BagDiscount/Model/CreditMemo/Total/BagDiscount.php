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

namespace Pratech\BagDiscount\Model\CreditMemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Bag Discount Total Segment
 */
class BagDiscount extends AbstractTotal
{
    /**
     * BagDiscount Constructor
     *
     * @param  \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setBagDiscount(0);
        $creditmemo->setBaseBagDiscount(0);

        $amount = $creditmemo->getOrder()->getBagDiscount();
        $creditmemo->setBagDiscount($amount);

        $amount = $creditmemo->getOrder()->getBaseBagDiscount();
        $creditmemo->setBaseBagDiscount($amount);

        return $this;
    }
}
