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
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Order\Model\ShipmentStatusFactory;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\OptionSource\Grid;

class Save extends Action
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
     * @param Action\Context $context
     * @param RequestRepositoryInterface $repository
     * @param DataObject $dataObject
     * @param ShipmentStatusFactory $shipmentStatusFactory
     * @param OrderReturnHelper $orderReturnHelper
     * @param Grid $grid
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $repository,
        private DataObject                 $dataObject,
        private ShipmentStatusFactory      $shipmentStatusFactory,
        private OrderReturnHelper          $orderReturnHelper,
        private Grid                       $grid
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
                $originalStatus = $model->getStatus();
                if ($status = $this->getRequest()->getParam(RequestInterface::STATUS)) {
                    $model->setStatus($status);
                }

                $this->processItems($model, $this->getRequest()->getParam('return_items'));

                if ($refundStatus = $this->getRequest()->getParam(RequestInterface::REFUND_STATUS)) {
                    $model->setRefundStatus($refundStatus);
                }

                $model->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID));

                if ($note = $this->getRequest()->getParam(RequestInterface::NOTE)) {
                    $model->setNote($note);
                }

                $this->repository->save($model);
                $this->eventManager->dispatch(
                    'return_request_saved',
                    ['request' => $model]
                );

                $this->messageManager->addSuccessMessage(__('You saved the return request.'));

                if ($this->getRequest()->getParam('back')) {
                    $this->getOriginalGrid($status, $originalStatus);

                    return $this->resultRedirectFactory->create()
                        ->setPath('*/*/view', ['request_id' => $model->getId()]);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->resultRedirectFactory->create()
                    ->setPath('*/*/view', ['request_id' => $requestId]);
            }
        }

        $returnGrid = $this->getOriginalGrid($status, $originalStatus);

        return $this->resultRedirectFactory->create()->setPath("*/*/$returnGrid");
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
                        if ($requestItem->getItemStatus() != 1 && $currentItem->getData('status') == 1) {
                            $approveStatusId = $this->orderReturnHelper->getStatusId('return_approved');
                            $model->setStatus($approveStatusId);
                        }
                        $requestItem->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID));
                        $rowItems[] = $requestItem;
                    } else {
                        $itemImages = $currentItem->getData('item_images')
                            ? json_encode($currentItem->getData('item_images')) : [];

                        $splitItem = $this->repository->getEmptyRequestItemModel();
                        $splitItem->setRequestId($requestItem->getRequestId())
                            ->setOrderItemId($requestItem->getOrderItemId())
                            ->setQty($currentItem->getData(RequestItemInterface::QTY))
                            ->setItemStatus($currentItem->getData('status'))
                            ->setReasonId($currentItem->getData(RequestItemInterface::REASON_ID))
                            ->setComment($currentItem->getData('comment'))
                            ->setImages($itemImages);
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

    /**
     * @param int $status
     * @param int $originalStatus
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getOriginalGrid($status, $originalStatus)
    {
        $newStatus = $this->shipmentStatusFactory->create()->load($status)->getStatusCode();
        $originalStatus = $this->shipmentStatusFactory->create()->load($originalStatus)->getStatusCode();
        $gridId = 0;

        if (!$returnGrid = $this->_session->getreturnOriginalGrid()) {
            switch ($originalStatus) {
                case 'refund_completed':
                    $returnGrid = 'archive';
                    $gridId = 2;
                    break;
                case 'return_pending':
                case 'return_approved':
                    $returnGrid = 'pending';
                    $gridId = 1;
                    break;
                default:
                    $returnGrid = 'manage';
                    break;
            }

            $this->_session->setreturnOriginalGrid($returnGrid);
        }

        if ($newStatus !== $originalStatus) {
            $newGrid = $this->grid->toArray()[$gridId];
            $this->messageManager->addNoticeMessage(
                __('The return request has been moved to %1 grid.', $newGrid)
            );
        }

        return $returnGrid;
    }
}
