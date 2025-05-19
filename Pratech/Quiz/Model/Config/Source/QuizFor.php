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
 * Class Quiz For
 * Provides options for Quiz types
 */
class QuizFor implements OptionSourceInterface
{
    public const CUSTOMER = 'customer';
    public const GUEST = 'guest';
    public const BOTH = 'both';

    /**
     * Get options for reward types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CUSTOMER, 'label' => __('Customer')],
            ['value' => self::GUEST, 'label' => __('Guest')],
            ['value' => self::BOTH, 'label' => __('Both')],
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
            self::CUSTOMER => __('Customer'),
            self::GUEST => __('Guest'),
            self::BOTH => __('Both'),
        ];
    }
}
