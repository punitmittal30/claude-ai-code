<?php

namespace Pratech\Customer\Controller\Adminhtml\Blocked;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Customer\Model\BlockedCustomersFactory;

class Save extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Save constructor
     *
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param BlockedCustomersFactory $blockedCustomersFactory
     * @param Json $json
     */
    public function __construct(
        Action\Context                  $context,
        RedirectFactory                 $redirectFactory,
        private BlockedCustomersFactory $blockedCustomersFactory,
        private Json                    $json
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                $id = $data['entity_id'];
                $blockedCustomer = $this->blockedCustomersFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['entity_id']);
                }

                $blockedCustomer->setData($data);
                $blockedCustomer->save();

                $this->_eventManager->dispatch(
                    'blocked_customer_controller_save_after',
                    ['blocked_customer' => $blockedCustomer]
                );

                $this->messageManager->addSuccessMessage(__('Blocked Customer data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirect->setPath(
                        '*/*/edit',
                        ['entity_id' => $blockedCustomer->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Customer::manage');
    }
}
