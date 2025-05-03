<?php
/**
 * Hyuga_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Cart
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Cart\Model\System\Config\Source\CrossSell;

use Magento\Framework\Option\ArrayInterface;

class Mode implements ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'allcartitems',
                'label' => __('All Cart Items')
            ],
            [
                'value' => 'lastcartitem',
                'label' => __('Last Cart Item')
            ],
        ];
    }
}
