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

class ItemStatus implements ArrayInterface
{
    public const PENDING = 0;
    public const AUTHORIZED = 1;
    public const RECEIVED = 2;
    public const RESOLVED = 3;
    public const REJECTED = 4;

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
            self::PENDING => __('Pending'),
            self::AUTHORIZED => __('Authorized'),
            self::RECEIVED => __('Received'),
            self::RESOLVED => __('Resolved'),
            self::REJECTED => __('Rejected'),
        ];
    }
}
