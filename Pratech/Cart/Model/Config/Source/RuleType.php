<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Rule Type Source Class
 */
class RuleType implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => 'Discount',
                'value' => 1
            ],
            [
                'label' => 'Freebie',
                'value' => 2
            ],
            [
                'label' => 'Cashback',
                'value' => 3
            ]
        ];
    }
}
