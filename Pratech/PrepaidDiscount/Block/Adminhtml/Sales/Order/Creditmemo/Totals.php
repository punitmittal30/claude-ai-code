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

namespace Pratech\PrepaidDiscount\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Credit Memo Totals Class
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * Order invoice
     *
     * @var Creditmemo|null
     */
    protected $_creditmemo = null;

    /**
     * @var DataObject
     */
    protected $_source;

    /**
     * Get data (totals) source model
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Credit Memo
     *
     * @return mixed
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Initialize payment prepaid discount totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getPrepaidDiscount()) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject(
            [
                'code' => 'prepaid_discount',
                'value' => $this->getSource()->getPrepaidDiscount(),
                'label' => 'Prepaid Discount',
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
