<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class LoggingLevel implements ArrayInterface
{
    /**
     * Logging level options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '100', 'label' => __('Debug')],
            ['value' => '200', 'label' => __('Info')],
            ['value' => '300', 'label' => __('Notice')],
            ['value' => '400', 'label' => __('Warning')],
            ['value' => '500', 'label' => __('Error')],
            ['value' => '600', 'label' => __('Critical')],
            ['value' => '700', 'label' => __('Alert')],
            ['value' => '800', 'label' => __('Emergency')]
        ];
    }
}
