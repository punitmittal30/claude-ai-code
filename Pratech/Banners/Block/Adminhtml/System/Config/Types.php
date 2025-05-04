<?php

namespace Pratech\Banners\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Types Class to provide frontend model for Banner Types.
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
        $this->addColumn('key', ['label' => __('Key')]);
        $this->addColumn('value', ['label' => __('Value')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
