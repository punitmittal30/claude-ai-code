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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;
use Pratech\Return\Model\VinculumIntegration;

class ProcessRequest extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Pratech_Return::request_save';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param Context $context
     * @param RequestRepositoryInterface $repository
     * @param DataObject $dataObject
     * @param VinculumIntegration $vinculumIntegration
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $repository,
        private DataObject                 $dataObject,
        private VinculumIntegration        $vinculumIntegration,
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

                $instantRefund = $this->getRequest()->getParam('instant_refund');
                $model->setInstantRefund($instantRefund);

                $result = $this->orderReturnHelper->calculateRefundedAmountAndStoreCredit($model);

                $model->setRefundedAmount($result['refunded_amount']);
                $model->setRefundedStoreCredit($result['refunded_store_credit']);
                $this->repository->save($model);

                $response = $this->vinculumIntegration->processReturnRequest($model);
                if (isset($response['requestStatus'])) {
                    $requestStatus = $response['requestStatus'];
                    $status = $requestStatus['status'];
                    $outputKey = $requestStatus['outputKey'] ?? null;
                    $errorDesc = $requestStatus['errorDesc'] ?? null;

                    if ($status === 'Success') {
                        $statusId = $this->orderReturnHelper->getStatusId('return_initiated');

                        if ($statusId) {
                            $model->setStatus($statusId);
                        }
                        $model->setIsProcessed(1);
                        $model->setVinReturnNumber($outputKey);
                        $this->repository->save($model);
                        $this->eventManager->dispatch(
                            'return_manager_rma_saved',
                            ['request' => $model]
                        );

                        $this->messageManager->addSuccessMessage(__('Return request processed successfully.'));
                    } else {
                        $this->messageManager->addErrorMessage(
                            $errorDesc ?: 'An error occurred during the return request.'
                        );
                    }
                } else {
                    $this->messageManager->addErrorMessage('Invalid response from the API.');
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->resultRedirectFactory->create()
                    ->setPath('*/*/view', ['request_id' => $requestId]);
            }
        }
        return $this->resultRedirectFactory->create()->setPath("*/*/view", ['request_id' => $requestId]);
    }

    /**
     * Process Items
     *
     * @param RequestInterface $model
     * @param array $items
     * @return void
     * @throws LocalizedException
     */
    public function processItems(RequestInterface $model, array $items): void
    {
        $resultItems = [];

        $currentRequestItems = [];

        foreach ($model->getRequestItems() as $requestItem) {
            if (empty($currentRequestItems[$requestItem->getOrderItemId()])) {
                $currentRequestItems[$requestItem->getOrderItemId()] = [];
            }

            $currentRequestItems[$requestItem->getOrderItemId()][$requestItem->getRequestItemId()] = $requestItem;
        }

        foreach ($currentRequestItems as $currentRequestItem) {
            $currentItems = false;
            $requestQty = 0;

            foreach ($items as $item) {
                if (!empty($item[0]) && !empty($item[0][RequestItemInterface::REQUEST_ITEM_ID])
                    && !empty($currentRequestItem[(int)$item[0][RequestItemInterface::REQUEST_ITEM_ID]])
                ) {
                    $currentItems = $item;
                    $requestQty = $currentRequestItem[(int)$item[0][RequestItemInterface::REQUEST_ITEM_ID]]
                        ->getRequestQty();
                    break;
                }
            }

            if ($currentItems) {
                $rowItems = [];

                foreach ($currentItems as $currentItem) {
                    $currentItem = $this->dataObject->unsetData()->setData($currentItem);

                    if (!empty($currentItem->getData(RequestItemInterface::REQUEST_ITEM_ID))
                        && ($requestItem = $currentRequestItem[$currentItem
                            ->getData(RequestItemInterface::REQUEST_ITEM_ID)])
                    ) {
                        $requestItem->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID));
                        $rowItems[] = $requestItem;
                    } else {
                        $splitItem = $this->repository->getEmptyRequestItemModel();
                        $splitItem->setRequestId($requestItem->getRequestId())
                            ->setOrderItemId($requestItem->getOrderItemId())
                            ->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID));
                        $rowItems[] = $splitItem;
                    }
                }

                $newQty = 0;

                foreach ($rowItems as $rowItem) {
                    $newQty += $rowItem->getQty();
                    $resultItems[] = $rowItem;
                }

                if ($newQty != $requestQty) {
                    throw new LocalizedException(__('Wrong Request Qty'));
                }
            } elseif (!empty($currentRequestItem[0])) {
                $resultItems[] = $currentRequestItem[0];
            }
        }

        $model->setRequestItems($resultItems);
    }
}
