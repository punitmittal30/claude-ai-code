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
    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';

    /**
     * Get Enable and Disable function
     */
    public function toOptionArray()
    {
        $options = [
                [
                    'label' => __("Active"),
                    'value' => self::ACTIVE,
                ],
                [
                    'label' => __("Cancelled"),
                    'value' => self::CANCELLED,
                ],
                [
                    'label' => __("Completed"),
                    'value' => self::COMPLETED,
                ]
            ];

        return $options;
    }

    /**
     * Get status label by value
     *
     * @param string $value
     * @return string
     */
    public function getStatusLabel(string $value): string
    {
        $options = $this->toArray();
        return $options[$value] ?? '';
    }

    /**
     * Get options in 'key-value' format
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = $this->toOptionArray();
        $result = [];

        foreach ($options as $option) {
            $result[$option['value']] = $option['label'];
        }

        return $result;
    }
}
