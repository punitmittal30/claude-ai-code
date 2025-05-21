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

namespace Pratech\CustomDeliveryCharges\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Creditmemo;
use Pratech\CustomDeliveryCharges\Helper\Data;

/**
 * Creditmemo Totals Class
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
     * @var Data
     */
    protected $deliveryChargesHelper;

    /**
     * Creditmemo totals constructor
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
     * Get Credit Memo
     *
     * @return mixed
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Initialize payment delivery charges totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getDeliveryCharges()) {
            return $this;
        }

        $total = new DataObject(
            [
                'code' => 'delivery_charges',
                'strong' => false,
                'value' => $this->getSource()->getDeliveryCharges(),
                'label' => $this->deliveryChargesHelper->getDeliveryChargesLabel(),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
