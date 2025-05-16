<?php

namespace Pratech\Customer\Controller\Adminhtml\Blocked;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Customer\Model\BlockedCustomers;

class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var BlockedCustomers
     */
    protected $blockedCustomers;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Action\Context $context
     * @param PageFactory $pageFactory
     * @param Registry $registry
     * @param BlockedCustomers $blockedCustomers
     */
    public function __construct(
        Action\Context $context,
        PageFactory    $pageFactory,
        Registry       $registry,
        BlockedCustomers         $blockedCustomers
    ) {
        $this->registry = $registry;
        $this->blockedCustomers = $blockedCustomers;
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        $blockedCustomer = $this->blockedCustomers;
        if ($id) {
            $blockedCustomer->load($id);
            if (!$blockedCustomer->getId()) {
                $this->messageManager->addErrorMessage(__('This customer does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('customers/blocked/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $blockedCustomer->setData($data);
        }

        $this->registry->register('blocked_customer', $blockedCustomer);
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Blocked Customer');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Blocked Customer');
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
        return $this->_authorization->isAllowed('Magento_Customer::manage');
    }
}
