<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Block\Adminhtml\AttributeMapping\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Save and Continue to same author edit form.
 */
class SaveAndContinueButton extends Generic implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue'),
            'class' => 'save',
            'sort_order' => 40
        ];
    }
}
