<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\OptionSource;

use Magento\Framework\Option\ArrayInterface;

class Grid implements ArrayInterface
{
    public const MANAGE = 0;
    public const PENDING = 1;
    public const ARCHIVED = 2;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::MANAGE => __('Manage Requests'),
            self::PENDING => __('Customers\' Pending Requests'),
            self::ARCHIVED => __('Archived Requests')
        ];
    }
}
