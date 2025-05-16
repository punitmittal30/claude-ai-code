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

namespace Pratech\Return\Block\Adminhtml\Buttons\Reason;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Pratech\Return\Block\Adminhtml\Buttons\GenericButton;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get Button Data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->getReasonId()) {
            return [];
        }
        $alertMessage = __('Are you sure you want to do this?');
        $onClick = sprintf('deleteConfirm("%s", "%s")', $alertMessage, $this->getDeleteUrl());

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'id' => 'condition-edit-delete-button',
            'on_click' => $onClick,
            'sort_order' => 20,
        ];
    }

    /**
     * Get Reason ID.
     *
     * @return null|int
     */
    public function getReasonId(): ?int
    {
        return (int)$this->request->getParam('reason_id');
    }

    /**
     * Get Delete URL.
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['reason_id' => $this->getReasonId()]);
    }
}
