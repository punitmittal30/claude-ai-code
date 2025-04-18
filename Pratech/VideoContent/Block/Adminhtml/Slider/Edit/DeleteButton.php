<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Block\Adminhtml\Slider\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Delete Button in Slider Edit Form.
 */
class DeleteButton extends Generic implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array|void
     */
    public function getButtonData(): array
    {
        $data = [];

        if ($this->getId()) {
            $data = [
                'label' => __('Delete'),
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this carousel?')
                    . '\',\'' . $this->getDeleteUrl() . '\')',
                'class' => 'delete',
                'sort_order' => 20
            ];
        }

        return $data;
    }

    /**
     * Get Delete URL
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getId()]);
    }
}
