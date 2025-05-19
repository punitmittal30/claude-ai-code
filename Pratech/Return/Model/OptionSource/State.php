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

class State implements ArrayInterface
{
    public const PENDING = 0;
    public const AUTHORIZED = 1;
    public const PROCESSING = 2;
    public const RECEIVED = 3;
    public const RESOLVED = 4;
    public const CANCELED = 5;

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
            self::AUTHORIZED => __('Approved'),
            self::PROCESSING => __('Processing'),
            self::RECEIVED => __('Delivered'),
            self::RESOLVED => __('Completed'),
            self::CANCELED => __('Canceled')
        ];
    }
}
