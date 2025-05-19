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

namespace Pratech\DiscountReport\Block\Adminhtml\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Exception\LocalizedException;

class Form extends Generic
{

    /**
     * Initialize the form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('discount_report_export_form');
        $this->setTitle(__('Discount Report Export Form'));
    }

    /**
     * Build the form elements
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $actionUrl = $this->getUrl('*/*/export');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $actionUrl,
                    'method' => 'post'
                ]
            ]
        );

        // Add a fieldset for the base configuration
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Filter')]);

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);

        $fieldset->addField(
            'from',
            'date',
            [
                'name' => 'from',
                'date_format' => $dateFormat,
                'label' => __('From'),
                'title' => __('From'),
                'required' => true,
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField(
            'to',
            'date',
            [
                'name' => 'to',
                'date_format' => $dateFormat,
                'label' => __('To'),
                'title' => __('To'),
                'required' => true,
                'css_class' => 'admin__field-small',
                'class' => 'admin__control-text'
            ]
        );

        // Set the form to use a container
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
