<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pratech\Recurring\Model\Config\Source;

/**
 * Custom Attribute Renderer
 */
class Duration extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
