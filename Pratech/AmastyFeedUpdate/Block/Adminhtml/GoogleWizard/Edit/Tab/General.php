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

namespace Pratech\AmastyFeedUpdate\Block\Adminhtml\GoogleWizard\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Amasty\Feed\Block\Adminhtml\GoogleWizard\Edit\Tab\General as GeneralTab;

/**
 * Prepare GoogleWizard Form
 */
class General extends GeneralTab
{
    
    /**
     * Feed Block Constructor
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param \Magento\Framework\Registry              $registry
     * @param \Magento\Framework\Data\FormFactory      $formFactory
     * @param \Amasty\Feed\Model\RegistryContainer     $registryContainer
     * @param \Amasty\Feed\Model\GoogleWizard          $googleWizard
     * @param \Magento\Store\Model\System\Store        $systemStore
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Amasty\Feed\Model\FeedRepository        $feedRepository
     * @param array                                    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Amasty\Feed\Model\RegistryContainer $registryContainer,
        private \Amasty\Feed\Model\GoogleWizard $googleWizard,
        private \Magento\Store\Model\System\Store $systemStore,
        private \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        private \Amasty\Feed\Model\FeedRepository $feedRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $registryContainer,
            $googleWizard,
            $systemStore,
            $currencyFactory,
            $feedRepository,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    protected function prepareNotEmptyForm()
    {
        if ($feedId = $this->_request->getParam('amfeed_id')) {
            try {
                $model = $this->feedRepository->getById($feedId);
            } catch (NoSuchEntityException $exception) {
                $model = $this->feedRepository->getEmptyModel();
            }
        } else {
            $model = $this->feedRepository->getEmptyModel();
        }

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('feed_');

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix(self::HTML_ID_PREFIX);

        $fieldset = $form->addFieldset(
            'general_fieldset',
            ['legend' => $this->getLegend()]
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
                'label' => __('Feed Name'),
                'title' => __('Feed Name'),
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

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField(
                'store_id',
                'select',
                [
                    'label' => __('Store View'),
                    'class' => 'required-entry',
                    'required' => true,
                    'name' => 'store_id',
                    'value' => $this->googleWizard->getStoreId(),
                    'values' => $this->systemStore->getStoreValuesForForm()
                ]
            );
        } else {
            $fieldset->addField(
                'store_id',
                'hidden',
                [
                    'value' => $this->googleWizard->getStoreId()
                ]
            );
        }

        $fieldset->addField(
            'format_price_currency',
            'select',
            [
                'label' => __('Price Currency'),
                'name'  => 'format_price_currency',
                'value' => $this->googleWizard->getCurrency(),
                'options' => $this->getCurrencyList(),
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

        return $this;
    }
}
