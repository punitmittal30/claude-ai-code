<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * MappingValue Option Provider Class
 */
class MediaStatus implements OptionSourceInterface
{
    public const APPROVED = 1;
    public const PENDING = 2;
    public const NOT_APPROVED = 3;

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getOptions() as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $options;
    }

    /**
     * Get Options
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [
            self::APPROVED => __('Approved'),
            self::PENDING => __('Pending'),
            self::NOT_APPROVED => __('Not Approved')
        ];

        return $options;
    }
}
