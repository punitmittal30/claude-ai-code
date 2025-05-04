<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DurationType implements OptionSourceInterface
{
    public const DAY  = 'day';
    public const WEEK = 'week';
    public const MONTH = 'month';
    public const YEAR = 'year';

    /**
     * Get duration type items
     */
    public function toOptionArray()
    {
        $options = [
                [
                    'label' => __("Day"),
                    'value' => self::DAY,
                ],
                [
                    'label' => __("Week"),
                    'value' => self::WEEK,
                ],
                [
                    'label' => __("Month"),
                    'value' => self::MONTH,
                ],
                [
                    'label' => __("Year"),
                    'value' => self::YEAR,
                ]
            ];

        return $options;
    }
}
