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
 * MRP Total Total Segment
 */
class MRPTotal extends AbstractTotal
{
    /**
     * MRPTotal Constructor
     *
     * @param  \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setMrpTotal(0);
        $creditmemo->setBaseMrpTotal(0);

        $amount = $creditmemo->getOrder()->getMrpTotal();
        $creditmemo->setMrpTotal($amount);

        $amount = $creditmemo->getOrder()->getBaseMrpTotal();
        $creditmemo->setBaseMrpTotal($amount);

        return $this;
    }
}
