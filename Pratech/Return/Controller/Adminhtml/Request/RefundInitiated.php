<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

class RefundInitiated extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Pratech_Return::refund_initiate';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param Action\Context $context
     * @param RequestRepositoryInterface $repository
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $repository,
        private OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context);
        $this->eventManager = $context->getEventManager() ?: ObjectManager::getInstance()
            ->get(ManagerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $requestId = "";
        if ($this->getRequest()->getParams()) {
            try {
                if (!($requestId = (int)$this->getRequest()->getParam('request_id'))) {
                    return $this->resultRedirectFactory->create()->setPath('*/*/pending');
                }

                $model = $this->repository->getById($requestId);

                $result = $this->orderReturnHelper->triggerRefundForReturn($model, "REFUND_INITIATED");
                if ($result) {
                    $statusId = $this->orderReturnHelper->getStatusId('refund_initiated');

                    if ($statusId) {
                        $model->setRefundStatus($statusId);
                    }
                    $this->repository->save($model);
                    $this->eventManager->dispatch(
                        'return_manager_refund_initiated',
                        ['request' => $model]
                    );

                    $this->messageManager->addSuccessMessage(__('Refund initiated successfully.'));
                } else {
                    $this->messageManager->addErrorMessage('An error occurred while initiating the refund.');
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->resultRedirectFactory->create()
                    ->setPath('*/*/view', ['request_id' => $requestId]);
            }
        }
        return $this->resultRedirectFactory->create()->setPath("*/*/view", ['request_id' => $requestId]);
    }
}
