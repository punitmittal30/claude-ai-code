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
 * Platform Used Source Class
 */
class PlatformUsed implements OptionSourceInterface
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
                'label' => 'Website',
                'value' => 1
            ],
            [
                'label' => 'App',
                'value' => 2
            ],
            [
                'label' => 'Both',
                'value' => 3
            ]
        ];
    }
}
