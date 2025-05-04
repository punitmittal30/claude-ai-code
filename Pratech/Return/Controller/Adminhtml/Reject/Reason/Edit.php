<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Controller\Adminhtml\Reject\Reason;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Pratech\Return\Model\RejectReason\RejectReasonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Action
{

    /**
     * Edit Reason Constructor
     *
     * @param Action\Context $context
     * @param RejectReasonFactory $rejectReasonFactory
     */
    public function __construct(
        Action\Context                $context,
        protected RejectReasonFactory $rejectReasonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Pratech_Return::reject_reason');

        if ($reasonId = (int)$this->getRequest()->getParam('reason_id')) {
            try {
                $this->rejectReasonFactory->create()->load($reasonId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Reject Reason'));
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This reason no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Reject Reason'));
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
        return $this->_authorization->isAllowed('Pratech_Return::reject_reason');
    }
}
