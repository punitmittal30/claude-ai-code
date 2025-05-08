<?php
/**
 * Pratech_Quiz
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Quiz
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Quiz\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class RewardType
 * Provides options for Quiz reward types
 */
class RewardType implements OptionSourceInterface
{
    public const REWARD_COUPON = 'coupon';
    public const REWARD_HCASH = 'hcash';

    /**
     * Get options for reward types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::REWARD_COUPON, 'label' => __('Coupon')],
            ['value' => self::REWARD_HCASH, 'label' => __('H-Cash')],
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::REWARD_COUPON => __('Coupon'),
            self::REWARD_HCASH => __('H-Cash'),
        ];
    }
}
