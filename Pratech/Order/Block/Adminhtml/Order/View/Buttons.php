<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Block\Adminhtml\Order\View;

class Buttons extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();

        if (!$this->getOrderId()) {
            return;
        }

        $order = $this->getOrder();

        if (!$order) {
            return;
        }

        if ($this->_isAllowedAction('Magento_Sales::sales_order') && $order->getStatus() === 'pending') {
            $buttonUrl = $this->_urlBuilder->getUrl(
                'sales/order/processing',
                ['order_id' => $this->getOrderId()]
            );

            $this->addButton(
                'sales_order_processing',
                [
                    'label' => __('Confirm Order'),
                    'onclick' => 'setLocation(\'' . $buttonUrl . '\')'
                ],
            );
        }
        return $this;
    }
}
