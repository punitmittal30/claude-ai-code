<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Block\Adminhtml\Order\PartialCancel\Create;

use Magento\Sales\Block\Adminhtml\Items\AbstractItems;

/**
 * Adminhtml order partialcancel items grid
 */
class Items extends AbstractItems
{
    /**
     * Prepare child blocks
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'submit_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Submit'),
                'class' => 'save submit-button primary',
                'onclick' => 'submitPartialOrderCancellation()'
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Retrieve source
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Retrieve order totals block settings
     *
     * @return array
     */
    public function getOrderTotalData()
    {
        return [];
    }

    /**
     * Retrieve order total bar block data
     *
     * @return array
     */
    public function getOrderTotalbarData()
    {
        $this->setPriceDataObject($this->getOrder());

        $totalBarData = [];
        $totalBarData[] = [__('Paid Amount'), $this->displayPriceAttribute('total_invoiced'), false];
        $totalBarData[] = [__('Refund Amount'), $this->displayPriceAttribute('total_refunded'), false];
        $totalBarData[] = [__('Shipping Amount'), $this->displayPriceAttribute('shipping_invoiced'), false];
        $totalBarData[] = [__('Shipping Refund'), $this->displayPriceAttribute('shipping_refunded'), false];
        $totalBarData[] = [__('Order Grand Total'), $this->displayPriceAttribute('grand_total'), true];
        return $totalBarData;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Check if allow to edit qty
     *
     * @return bool
     */
    public function canEditQty()
    {
        if ($this->getOrder()->getPayment()->canRefund()) {
            return $this->getOrder()->getPayment()->canRefundPartialPerInvoice();
        }
        return true;
    }
}
