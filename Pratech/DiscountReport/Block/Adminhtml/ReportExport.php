<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\DiscountReport\Block\Adminhtml;

class ReportExport extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Pratech_DiscountReport';
        $this->_controller = 'adminhtml';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->update('save', 'label', __('Export Report'));
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Orders Discount Report');
    }
}
