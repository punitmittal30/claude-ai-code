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

declare(strict_types=1);

namespace Pratech\Promotion\Block\Adminhtml\PromoCode\Edit\Tab\Promo;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Pratech\Promotion\Helper\Code;

class Form extends Generic
{
    /**
     * @var Code
     */
    protected $_codeHelper = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Code $codeHelper
     * @param array $data
     */
    public function __construct(
        Context     $context,
        Registry    $registry,
        FormFactory $formFactory,
        Code        $codeHelper,
        array       $data = []
    ) {
        $this->_codeHelper = $codeHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare promo codes generation parameters form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $codeHelper = $this->_codeHelper;

        $model = $this->_coreRegistry->registry("campaign");
        $campaignId = $model->getCampaignId();

        $form->setHtmlIdPrefix('codes_');

        $gridBlock = $this->getLayout()->getBlock('promotion_campaign_edit_tab_promo_grid');
        $gridBlockJsObject = '';
        if ($gridBlock) {
            $gridBlockJsObject = $gridBlock->getJsObjectName();
        }

        $fieldset = $form->addFieldset('information_fieldset', []);
        $fieldset->addClass('ignore-validate');

        $fieldset->addField('campaign_id', 'hidden', ['name' => 'campaign_id', 'value' => $campaignId]);

        $fieldset->addField(
            'qty',
            'text',
            [
                'name' => 'qty',
                'label' => __('Promo Code Qty'),
                'title' => __('Promo Code Qty'),
                'required' => true,
                'class' => 'validate-digits validate-greater-than-zero',
                'onchange' => 'window.validatePromoCodeGenerate(this)'
            ]
        );

        $fieldset->addField(
            'length',
            'text',
            [
                'name' => 'length',
                'label' => __('Code Length'),
                'title' => __('Code Length'),
                'required' => true,
                'value' => $codeHelper->getDefaultLength(),
                'class' => 'validate-digits validate-greater-than-zero',
                'onchange' => 'window.validatePromoCodeGenerate(this)'
            ]
        );

        $fieldset->addField(
            'format',
            'select',
            [
                'label' => __('Code Format'),
                'name' => 'format',
                'options' => $codeHelper->getFormatsList(),
                'required' => true,
                'value' => $codeHelper->getDefaultFormat(),
                'onchange' => 'window.validatePromoCodeGenerate(this)'
            ]
        );

        $idPrefix = $form->getHtmlIdPrefix();
        $generateUrl = $this->getGenerateUrl();

        $fieldset->addField(
            'generate_button',
            'note',
            [
                'text' => $this->getButtonHtml(
                    __('Generate'),
                    "generatePromoCodes('{$idPrefix}' ,'{$generateUrl}', '{$gridBlockJsObject}')",
                    'generate'
                )
            ]
        );

        $this->setForm($form);

//        $this->_eventManager->dispatch(
//            'adminhtml_promo_quote_edit_tab_coupons_form_prepare_form',
//            ['form' => $form]
//        );

        return parent::_prepareForm();
    }

    /**
     * Retrieve URL to Generate Action
     *
     * @return string
     */
    public function getGenerateUrl()
    {
        return $this->getUrl('promotion/promocode/generate');
    }
}
