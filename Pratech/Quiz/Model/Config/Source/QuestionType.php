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
 * Class QuestionType
 * Provides options for Quiz reward types
 */
class QuestionType implements OptionSourceInterface
{
    public const REWARD_COUPON = 'mcq';
    public const REWARD_HCASH = 'image';

    /**
     * Get options for reward types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::REWARD_COUPON, 'label' => __('MCQ')],
            ['value' => self::REWARD_HCASH, 'label' => __('Image')],
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::REWARD_COUPON => __('MCQ'),
            self::REWARD_HCASH => __('Image'),
        ];
    }
}
