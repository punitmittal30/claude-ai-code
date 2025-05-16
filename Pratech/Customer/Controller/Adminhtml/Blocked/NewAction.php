<?php

namespace Pratech\Customer\Controller\Adminhtml\Blocked;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action
{
    /**
     * @var ForwardFactory
     */
    protected $forwardFactory;

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        Action\Context $context,
        ForwardFactory $forwardFactory
    ) {
        $this->forwardFactory = $forwardFactory;
        parent::__construct($context);
    }

    /**
     * Execute Method.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultForward = $this->forwardFactory->create();
        return $resultForward->forward('edit');
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
