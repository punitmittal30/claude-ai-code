<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Base\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Types Class to provide frontend model for Quick Links.
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
        $this->addColumn('label', ['label' => __('Label'), 'size' => 30]);
        $this->addColumn('url', ['label' => __('Url'), 'size' => 70]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Link');
    }
}
