<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Block\Adminhtml\BlockedCustomers\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Save and Continue to same banner edit form.
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
