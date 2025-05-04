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

namespace Pratech\Return\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\StatusRepositoryInterface;

class Edit extends Action
{
    /**
     * @param Action\Context $context
     * @param StatusRepositoryInterface $repository
     */
    public function __construct(
        Action\Context                    $context,
        private StatusRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /**
         * @var Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Pratech_Return::status');

        if ($statusId = (int)$this->getRequest()->getParam('status_id')) {
            try {
                $this->repository->getById($statusId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Status'));
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This status no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Status'));
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
        return $this->_authorization->isAllowed('Pratech_Return::status');
    }
}
