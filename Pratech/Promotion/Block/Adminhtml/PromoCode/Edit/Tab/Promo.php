<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Block\Adminhtml\PromoCode\Edit\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;

class Promo extends Template implements TabInterface
{
    /**
     * Get Tab Label
     *
     * @return Phrase|string
     */
    public function getTabLabel()
    {
        return __('Manage Promo Codes');
    }

    /**
     * Get Tab Title
     *
     * @return Phrase|string
     */
    public function getTabTitle()
    {
        return __('Manage Promo Codes');
    }

    /**
     * Can Show Tab.
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is Tab Hidden?
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }
}
