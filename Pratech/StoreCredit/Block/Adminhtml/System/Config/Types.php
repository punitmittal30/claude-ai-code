<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Types Class to provide frontend model for Store Credit Types.
 */
class Types extends AbstractFieldArray
{
    /**
     * @var bool
     */
    protected $_addAfter = false;

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('registration_source', ['label' => __('Registration Source')]);
        $this->addColumn('amount', ['label' => __('Amount')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
