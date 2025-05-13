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

namespace Pratech\PrepaidDiscount\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Invoice;

/**
 * Invoice Totals Class
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * Order invoice
     *
     * @var Invoice|null
     */
    protected $_invoice = null;

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
     * Get Invoice
     *
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getInvoice();
        $this->getSource();

        if (!$this->getSource()->getPrepaidDiscount()) {
            return $this;
        }

        $total = new DataObject(
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
