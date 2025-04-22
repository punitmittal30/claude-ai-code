<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
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
