<?php

namespace Pratech\ReviewRatings\Plugin\Block\Adminhtml\Add;

use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Review\Block\Adminhtml\Rating\Detailed;
use Magento\Review\Helper\Data;
use Magento\Store\Model\System\Store;
use Pratech\ReviewRatings\Model\Config\Source\Position;

/**
 * Adminhtml add product review form
 */
class Form extends Generic
{
    /**
     * @var Data
     */
    protected $_reviewData = null;

    /**
     * Core system store model
     *
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param Data $reviewData
     * @param Position $positionConfig
     * @param array $data
     * @param SecureHtmlRenderer|null $htmlRenderer
     */
    public function __construct(
        Context             $context,
        Registry            $registry,
        FormFactory         $formFactory,
        Store               $systemStore,
        Data                $reviewData,
        private Position    $positionConfig,
        array               $data = [],
        ?SecureHtmlRenderer $htmlRenderer = null
    ) {
        $this->_reviewData = $reviewData;
        $this->_systemStore = $systemStore;
        $this->secureRenderer = $htmlRenderer ?: ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare add review form
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('add_review_form', ['legend' => __('Review Details')]);
        $beforeHtml = $this->secureRenderer->renderStyleAsTag('display: none;', '#edit_form');
        $fieldset->setBeforeElementHtml($beforeHtml);

        $fieldset->addField('product_name', 'note', ['label' => __('Product'), 'text' => 'product_name']);

        $fieldset->addField(
            'detailed-rating',
            'note',
            [
                'label' => __('Product Rating'),
                'required' => true,
                'text' => '<div id="rating_detail">' . $this->getLayout()->createBlock(
                    Detailed::class
                )->toHtml() . '</div>'
            ]
        );

        $fieldset->addField(
            'status_id',
            'select',
            [
                'label' => __('Status'),
                'required' => true,
                'name' => 'status_id',
                'values' => $this->_reviewData->getReviewStatusesOptionArray()
            ]
        );

        $fieldset->addField(
            'position',
            'select',
            [
                'label' => __('Position'),
                'required' => false,
                'name' => 'position',
                'values' => $this->positionConfig->toOptionArray()
            ]
        );

        $fieldset->addField(
            'power_review',
            'select',
            [
                'label' => __('Power Review'),
                'required' => false,
                'name' => 'power_review',
                'values' => ["1" => __('Yes'), "0" => __("No")],
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'select_stores',
                'multiselect',
                [
                    'label' => __('Visibility'),
                    'required' => true,
                    'name' => 'select_stores[]',
                    'values' => $this->_systemStore->getStoreValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField(
            'nickname',
            'text',
            [
                'name' => 'nickname',
                'title' => __('Nickname'),
                'label' => __('Nickname'),
                'maxlength' => '50',
                'required' => true
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'title' => __('Summary of Review'),
                'label' => __('Summary of Review'),
                'maxlength' => '255',
                'required' => false
            ]
        );

        $fieldset->addField(
            'keywords',
            'text',
            [
                'name' => 'keywords',
                'title' => __('Keywords'),
                'label' => __('Keywords'),
                'maxlength' => '255',
                'required' => false
            ]
        );

        $fieldset->addField(
            'detail',
            'textarea',
            [
                'name' => 'detail',
                'title' => __('Review'),
                'label' => __('Review'),
                'required' => false
            ]
        );

        $fieldset->addField('product_id', 'hidden', ['name' => 'product_id']);

        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setAction($this->getUrl('review/product/post'));

        $this->setForm($form);
    }
}
