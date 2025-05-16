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

namespace Pratech\Return\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;

class RejectRequest extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Pratech_Return::request_reject';

    /**
     * @param Context $context
     * @param RequestRepositoryInterface $repository
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $repository,
        private OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $requestId = "";
        if ($this->getRequest()->getParams()) {
            try {
                $requestId = (int)$this->getRequest()->getParam('request_id');
                $rejectReasonId = (int)$this->getRequest()->getParam('reject_reason');
                if (!$requestId || !$rejectReasonId) {
                    return $this->resultRedirectFactory->create()->setPath('*/*/pending');
                }

                $requestModel = $this->repository->getById($requestId);
                $rejectStatusId = $this->orderReturnHelper->getRejectStatusId();
                $requestModel->setStatus($rejectStatusId);
                $requestModel->setRejectReasonId($rejectReasonId);

                $this->repository->save($requestModel);

                $this->messageManager->addSuccessMessage(__('Return request rejected successfully.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->resultRedirectFactory->create()
                    ->setPath('*/*/view', ['request_id' => $requestId]);
            }
        }
        return $this->resultRedirectFactory->create()->setPath("*/*/view", ['request_id' => $requestId]);
    }
}
