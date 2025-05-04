<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Plugin\OrderView;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Magento\Sales\Model\Order;

class CreateReturnButton
{
    /**
     * @param OrderView $subject
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        $order = $subject->getOrder();

        $isEligibleForReturn = $this->isOrderEligibleForReturn($order);

        if ($isEligibleForReturn) {
            $subject->addButton(
                'return_create',
                [
                    'label' => __('Create Return'),
                    'class' => 'return-create-return-button',
                    'id' => 'return-create-return-button',
                    'onclick' => "setLocation('" . $subject->getUrl('return/request/create') . "')"
                ]
            );
        }
    }

    /**
     * Check if the order is eligible for a return based on the status or shipment status.
     *
     * @param  Order $order
     * @return bool
     */
    private function isOrderEligibleForReturn(Order $order): bool
    {
        if ($order->getStatus() === 'delivered') {
            return true;
        }

        foreach ($order->getShipmentsCollection() as $shipment) {
            if ((int)$shipment->getData('shipment_status') === 4) {
                return true;
            }
        }
        return false;
    }
}
