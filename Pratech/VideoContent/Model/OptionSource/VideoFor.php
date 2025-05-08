<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Video For
 * Provides options for Video types
 */
class VideoFor implements OptionSourceInterface
{
    public const CUSTOMER = 'customer';
    public const GUEST = 'guest';

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
        ];
    }
}
