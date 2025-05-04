<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Block\Adminhtml;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Phrase;

class ReviewImport extends Container
{
    /**
     * Get header text
     *
     * @return Phrase
     */
    public function getHeaderText()
    {
        return __('Reviews Importer');
    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Pratech_ReviewRatings';
        $this->_controller = 'adminhtml';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->update('save', 'label', __('Import Review'));
    }
}
