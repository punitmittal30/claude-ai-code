<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Block\Adminhtml\Video\Edit;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Delete Button in Catalog Video Edit Form.
 */
class DeleteButton extends Generic implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];

        if ($this->getVideoId()) {
            $data = [
                'label' => __('Delete'),
                'on_click' => 'deleteConfirm(\''
                    . __('Are you sure you want to delete this Video?')
                    . '\',\'' . $this->getDeleteUrl() . '\')',
                'class' => 'delete',
                'sort_order' => 20
            ];
        }

        return $data;
    }

    /**
     * Get Delete Url
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getVideoId()]);
    }
}
