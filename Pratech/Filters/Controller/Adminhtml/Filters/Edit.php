<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Controller\Adminhtml\Filters;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributes;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Filters\Model\FiltersPosition;

/**
 * Edit controller to manage edit action in filters management.
 */
class Edit extends Action
{

    /**
     * Edit constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param FiltersPosition $filtersPosition
     * @param EavAttributes $eavAttributes
     */
    public function __construct(
        protected Action\Context  $context,
        protected PageFactory     $pageFactory,
        protected FiltersPosition $filtersPosition,
        protected EavAttributes   $eavAttributes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     * @throws LocalizedException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $filtersPosition = $this->filtersPosition;
        if ($id) {
            $filtersPosition->load($id);
            if (!$filtersPosition->getId()) {
                $this->messageManager->addErrorMessage(
                    __('This Filters Position does not exists')
                );
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('pratech_filter/filters/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data) && $data['attribute_code'] != $filtersPosition->getAttributeCode()) {
            $attributeDetails = $this->eavAttributes
                ->loadByCode('catalog_product', $data["attribute_code"]);
            $data['attribute_id'] = $attributeDetails->getId();
            $data['attribute_name'] = $attributeDetails->getDefaultFrontendLabel();
        }
        if (!empty($data)) {
            $filtersPosition->setData($data);
        }

        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Filter Position');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Filter Position');
        }

        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Filters::filters_position');
    }
}
