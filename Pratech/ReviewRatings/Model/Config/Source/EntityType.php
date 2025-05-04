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
 * EntityType Option Provider Class
 */
class EntityType implements OptionSourceInterface
{

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
        $options = [];
        $entityTypeOptions = $this->getEntityTypeOptions();
        foreach ($entityTypeOptions as $entityTypeOption) {
            $options[$entityTypeOption['value']] = $entityTypeOption['label'];
        }

        return $options;
    }

    /**
     * Get EntityType Options
     *
     * @return array
     */
    public function getEntityTypeOptions(): array
    {
        $options = [
            [
                'value' => 'product',
                'label' => 'Product'
            ],
            [
                'value' => 'order',
                'label' => 'Order'
            ]
        ];
        return $options;
    }
}
