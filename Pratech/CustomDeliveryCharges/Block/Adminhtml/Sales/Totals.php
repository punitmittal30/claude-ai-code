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

namespace Pratech\CustomDeliveryCharges\Block\Adminhtml\Sales;

use Magento\Framework\View\Element\Template\Context;
use Pratech\CustomDeliveryCharges\Helper\Data;

/**
 * Totals Block Class
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    protected $deliveryChargesHelper;

    /**
     * Sales Totals Constructor
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
        parent::__construct($context, $data);
        $this->deliveryChargesHelper = $deliveryChargesHelper;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get Source
     *
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Init Totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getOrder();
        $this->getSource();

        if (!$this->getSource()->getDeliveryCharges()) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject(
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
