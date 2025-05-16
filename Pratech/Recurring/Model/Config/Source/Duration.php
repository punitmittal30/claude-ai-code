<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Custom Attribute Renderer
 */
class Duration extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
                ['label' => __('15 days'), 'value'=>'15'],
                ['label' => __('30 days'), 'value'=>'30'],
                ['label' => __('45 days'), 'value'=>'45'],
                ['label' => __('60 days'), 'value'=>'60'],
                ['label' => __('90 days'), 'value'=>'90']
            ];

        return $this->_options;
    }
}
