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

namespace Pratech\Return\Controller\Adminhtml\Reason;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Model\Reason\ReasonFactory;

class Edit extends Action
{

    /**
     * Edit Reason Constructor
     *
     * @param Action\Context $context
     * @param ReasonFactory $reasonFactory
     */
    public function __construct(
        Action\Context          $context,
        protected ReasonFactory $reasonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Pratech_Return::reason');

        if ($reasonId = (int)$this->getRequest()->getParam('reason_id')) {
            try {
                $this->reasonFactory->create()->load($reasonId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Reason'));
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This reason no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Reason'));
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
        return $this->_authorization->isAllowed('Pratech_Return::reason');
    }
}
