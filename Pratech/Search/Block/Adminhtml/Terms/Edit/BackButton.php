<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Block\Adminhtml\Terms\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Back Button in Search Terms Edit and Add Form
 */
class BackButton extends Generic implements ButtonProviderInterface
{

    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf(
                "location.href = '%s';",
                $this->getBackUrl()
            ),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get Back Url
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*');
    }
}
