<?php

namespace Pratech\Customer\Controller\Adminhtml\Blocked;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Customer\Model\BlockedCustomers;

class Delete extends Action
{
    /**
     * @var BlockedCustomers
     */
    protected $blockedCustomers;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * NewAction constructor
     *
     * @param Context $context
     * @param BlockedCustomers $blockedCustomers
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context   $context,
        BlockedCustomers $blockedCustomers,
        RedirectFactory  $redirectFactory
    ) {
        $this->blockedCustomers = $blockedCustomers;
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
        $id = $this->getRequest()->getParam('entity_id');
        if ($id) {
            $this->blockedCustomers->load($id);
            try {
                $this->blockedCustomers->delete();
                $this->_eventManager->dispatch('blocked_customers_controller_delete_after', ['blocked_customer' =>
                    $this->blockedCustomers]);
                $this->messageManager->addSuccessMessage(__('Blocked Customer has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirect->setPath('*/*/edit', ['entity_id' => $id]);
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
        return $this->_authorization->isAllowed('Magento_Customer::manage');
    }
}
