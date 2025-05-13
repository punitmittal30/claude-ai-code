<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Block\Adminhtml\Feed\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Amasty\Feed\Block\Adminhtml\Feed\Edit\Tab\General as GeneralTab;

/**
 * Prepare Feed Form
 */
class General extends GeneralTab
{
     /**
      * Feed Form Block Constructor
      *
      * @param \Magento\Backend\Block\Template\Context   $context
      * @param \Magento\Framework\Registry               $registry
      * @param \Magento\Framework\Data\FormFactory       $formFactory
      * @param \Magento\Store\Model\System\Store         $systemStore
      * @param \Amasty\Feed\Model\Config\Source\Compress $compressSource
      * @param array                                     $data
      */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        private \Magento\Store\Model\System\Store $systemStore,
        private \Amasty\Feed\Model\Config\Source\Compress $compressSource,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $systemStore,
            $compressSource,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_amfeed_feed');

        /**
         * @var \Magento\Framework\Data\Form $form
        */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('feed_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information')]
        );

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'feed_entity_id']);
        } else {
            $model->setData('is_active', 1);

            $model->setData('csv_column_name', 1);

            $model->setData('format_price_currency_show', 1);
            $model->setData('format_price_decimals', 'two');
            $model->setData('format_price_decimal_point', 'dot');
            $model->setData('format_price_thousands_separator', 'comma');

            $model->setData('format_date', 'Y-m-d');
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'filename',
            'text',
            [
                'name' => 'filename',
                'label' => __('File Name'),
                'title' => __('File Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'product_base_url',
            'text',
            [
                'name' => 'product_base_url',
                'label' => __('Product Base Url'),
                'title' => __('Product Base Url'),
                'required' => true
            ]
        );

        $typeOptions = [
            'label' => __('Type'),
            'title' => __('Type'),
            'name' => 'feed_type',
            'required' => true,

            'options' => [
                'csv' => __('CSV'),
                'xml' => __('XML'),
                'txt' => 'TXT'
            ]
        ];

        if ($model->getId()) {
            $typeOptions['readonly'] = true;
            $feedType = $model->getFeedType();
            $feedTypeText = $typeOptions['options'][$feedType];
            if ($feedType && $feedTypeText) {
                $typeOptions['options'] = [$feedType => $feedTypeText];
            }
        }

        $fieldset->addField(
            'feed_type',
            'select',
            $typeOptions
        );

        $fieldset->addField(
            'store_id',
            'select',
            [
                'name' => 'store_id',
                'label' => __('Store'),
                'title' => __('Store'),
                'required' => true,
                'options' => $this->systemStore->getStoreOptionHash()
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => [
                    '1' => __('Active'),
                    '0' => __('Inactive')
                ]
            ]
        );

        $fieldset->addField(
            'compress',
            'select',
            [
                'label' => __('Compress'),
                'name' => 'compress',
                'options' => $this->compressSource->toArray()
            ]
        );

        $fieldset->addField(
            'exclude_disabled',
            'select',
            [
                'label' => __('Exclude Disabled Products'),
                'title' => __('Exclude Disabled Products'),
                'name' => 'exclude_disabled',
                'options' => [
                    '1' => __('Yes'),
                    '0' => __('No')
                ]
            ]
        );
        $fieldset->addField(
            'exclude_out_of_stock',
            'select',
            [
                'label' => __('Exclude Out of Stock Products'),
                'title' => __('Exclude Out of Stock Products'),
                'name' => 'exclude_out_of_stock',
                'options' => [
                    '1' => __('Yes'),
                    '0' => __('No')
                ]
            ]
        );
        $fieldset->addField(
            'exclude_not_visible',
            'select',
            [
                'label' => __('Exclude Not Visible Products'),
                'title' => __('Exclude Not Visible Products'),
                'name' => 'exclude_not_visible',
                'options' => [
                    '1' => __('Yes'),
                    '0' => __('No')
                ]
            ]
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        return Generic::_prepareForm();
    }
}
