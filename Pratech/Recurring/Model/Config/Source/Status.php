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

class Status implements OptionSourceInterface
{
    public const ENABLED  = 1;
    public const DISABLED = 0;

    /**
     * Get Enable and Disable function
     */
    public function toOptionArray()
    {
        $options = [
                [
                    'label' => __("Enabled"),
                    'value' => self::ENABLED,
                ],
                [
                    'label' => __("Disabled"),
                    'value' => self::DISABLED,
                ]
            ];

        return $options;
    }
}
