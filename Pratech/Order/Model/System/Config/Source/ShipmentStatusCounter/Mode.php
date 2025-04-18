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

namespace Pratech\Order\Model\System\Config\Source\ShipmentStatusCounter;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $result = [
            [
                'value' => 'initial',
                'label' => __('Initial')
            ],
            [
                'value' => 'update',
                'label' => __('Update')
            ],
        ];
        return $result;
    }
}
