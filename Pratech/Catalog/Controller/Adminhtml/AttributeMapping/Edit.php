<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Controller\Adminhtml\AttributeMapping;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Catalog\Model\AttributeMapping;

/**
 * Edit controller to manage edit action in attributeMapping.
 */
class Edit extends Action
{
    /**
     * Edit constructor
     *
     * @param Action\Context   $context
     * @param PageFactory      $pageFactory
     * @param Registry         $registry
     * @param AttributeMapping $attributeMapping
     */
    public function __construct(
        Action\Context $context,
        protected PageFactory    $pageFactory,
        protected Registry       $registry,
        protected AttributeMapping         $attributeMapping
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $attributeMapping = $this->attributeMapping;
        if ($id) {
            $attributeMapping->load($id);
            if (!$attributeMapping->getId()) {
                $this->messageManager->addErrorMessage(__('This Attribute Mapping does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('custom/attributeMapping/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $attributeMapping->setData($data);
        }

        $this->registry->register('attributeMapping', $attributeMapping);
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Attribute Mapping');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Attribute Mapping');
        }

        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_Catalog::attributes_mapping');
    }
}
