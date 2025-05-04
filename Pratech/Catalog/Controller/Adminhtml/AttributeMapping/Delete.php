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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Catalog\Model\AttributeMapping;

/**
 * Class Delete
 * Delete controller to delete a attribute mapping.
 */
class Delete extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Delete AttributeMapping constructor
     *
     * @param Action\Context   $context
     * @param AttributeMapping $attributeMapping
     * @param RedirectFactory  $redirectFactory
     */
    public function __construct(
        Action\Context  $context,
        protected AttributeMapping          $attributeMapping,
        protected RedirectFactory $redirectFactory
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->attributeMapping->load($id);
            try {
                $this->attributeMapping->delete();
                $this->_eventManager->dispatch('attr_mapping_controller_delete_after', ['attributeMapping' => $this->attributeMapping]);
                $this->messageManager->addSuccessMessage(__('AttributeMapping has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirect->setPath('*/*/index');
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
