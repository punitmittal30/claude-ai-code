<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Type implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options[] = ['label' => 'Default', 'value' => ''];
        return array_merge($options, $this->getOptions());
    }

    /**
     * Get Options
     *
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            [
                'label' => __('Try Before You Buy'),
                'value' => __('try-before-you-buy')
            ],
            [
                'label' => __('Kiosk'),
                'value' => __('kiosk')
            ]
        ];
    }
}
