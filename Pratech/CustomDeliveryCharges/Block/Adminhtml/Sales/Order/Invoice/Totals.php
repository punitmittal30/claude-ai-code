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

namespace Pratech\CustomDeliveryCharges\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Invoice;
use Pratech\CustomDeliveryCharges\Helper\Data;

/**
 * Invoice Totals Class
 */
class Totals extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Data
     */
    protected $deliveryChargesHelper;

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
     * Invoice Totals Constructor
     *
     * @param Context $context
     * @param Data $deliveryChargesHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data    $deliveryChargesHelper,
        array   $data = []
    ) {
        $this->deliveryChargesHelper = $deliveryChargesHelper;
        parent::__construct($context, $data);
    }

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

        if (!$this->getSource()->getDeliveryCharges()) {
            return $this;
        }

        $total = new DataObject(
            [
                'code' => 'delivery_charges',
                'value' => $this->getSource()->getDeliveryCharges(),
                'label' => $this->deliveryChargesHelper->getDeliveryChargesLabel(),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
        return $this;
    }
}
