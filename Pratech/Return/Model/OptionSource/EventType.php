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

use Magento\Framework\Data\OptionSourceInterface;

class EventType implements OptionSourceInterface
{
    public const RMA_CREATED = 0;
    public const TRACKING_NUMBER_ADDED = 1;
    public const TRACKING_NUMBER_DELETED = 2;
    public const CUSTOMER_CLOSED_RMA = 3;
    public const STATUS_CHANGED_FROM_CLICKPOST = 4;
    public const MANAGER_SAVED_RMA = 5;
    public const SYSTEM_CHANGED_STATUS = 6;
    public const SYSTEM_CHANGED_MANAGER = 7;
    public const MANAGER_CHANGED_REFUND_STATUS = 8;

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
            self::RMA_CREATED => __('Return Request Created'),
            self::TRACKING_NUMBER_ADDED => __('Tracking Number Added'),
            self::TRACKING_NUMBER_DELETED => __('Tracking Number Deleted'),
            self::CUSTOMER_CLOSED_RMA => __('Customer Closed Return Request'),
            self::STATUS_CHANGED_FROM_CLICKPOST => __('Status Changed From Clickpost'),
            self::MANAGER_SAVED_RMA => __('Manager Saved Rma'),
            self::SYSTEM_CHANGED_STATUS => __('System Changed Status'),
            self::SYSTEM_CHANGED_MANAGER => __('System Changed Manager'),
            self::MANAGER_CHANGED_REFUND_STATUS => __('Manager Changed Refund Status')
        ];
    }
}
