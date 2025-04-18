<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Model\System\Config\Source\CrossSell;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $result = [
            [
                'value' => 'allcartitems',
                'label' => __('All Cart Items')
            ],
            [
                'value' => 'lastcartitem',
                'label' => __('Last Cart Item')
            ],
        ];
        return $result;
    }
}
