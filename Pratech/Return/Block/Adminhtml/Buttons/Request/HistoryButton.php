<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Block\Adminhtml\Buttons\Request;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Pratech\Return\Block\Adminhtml\Buttons\GenericButton;

class HistoryButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get Button Data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('History'),
            'class' => 'action- scalable',
            'id' => 'return-history-button',
            'on_click' => 'require("uiRegistry").get("return_request_form.return_request_form.modal").toggleModal();'
        ];
    }
}
