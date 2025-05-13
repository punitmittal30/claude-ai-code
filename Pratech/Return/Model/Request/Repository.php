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

namespace Pratech\Return\Model\Request;

use Exception;
use Magento\Framework\Data\Collection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestInterfaceFactory;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\RequestItemInterfaceFactory;
use Pratech\Return\Api\Data\TrackingInterface;
use Pratech\Return\Api\Data\TrackingInterfaceFactory;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory;
use Pratech\Return\Model\Request\ResourceModel\Request as RequestResource;
use Pratech\Return\Model\Request\ResourceModel\RequestItem as RequestItemResource;
use Pratech\Return\Model\Request\ResourceModel\RequestItemCollectionFactory;
use Pratech\Return\Model\Request\ResourceModel\Tracking as TrackingResource;
use Pratech\Return\Model\Request\ResourceModel\TrackingCollectionFactory;

class Repository implements RequestRepositoryInterface
{
    /**
     * @var RequestInterface[]
     */
    private $requests;

    /**
     * @param RequestInterfaceFactory $requestFactory
     * @param RequestItemInterfaceFactory $requestItemFactory
     * @param TrackingInterfaceFactory $trackingFactory
     * @param ManagerInterface $eventManager
     * @param RequestResource $requestResource
     * @param TrackingResource $trackingResource
     * @param RequestItemResource $requestItemResource
     * @param RequestItemCollectionFactory $requestItemCollectionFactory
     * @param TrackingCollectionFactory $trackingCollectionFactory
     */
    public function __construct(
        private RequestInterfaceFactory      $requestFactory,
        private RequestItemInterfaceFactory  $requestItemFactory,
        private TrackingInterfaceFactory     $trackingFactory,
        private ManagerInterface             $eventManager,
        private RequestResource              $requestResource,
        private TrackingResource             $trackingResource,
        private RequestItemResource          $requestItemResource,
        private RequestItemCollectionFactory $requestItemCollectionFactory,
        private TrackingCollectionFactory    $trackingCollectionFactory,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function saveTracking(TrackingInterface $tracking)
    {
        try {
            $this->trackingResource->save($tracking);
            $this->eventManager->dispatch(
                'manager_added_tracking_number_rma',
                ['tracking' => $tracking, 'request' => $this->getById($tracking->getRequestId())]
            );
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Unable to save new request. Error: %1', $e->getMessage()));
        }

        return $tracking;
    }

    /**
     * @inheritdoc
     */
    public function save(RequestInterface $request)
    {
        try {
            if ($request->getRequestId()) {
                $request = $this->getById($request->getRequestId())->addData($request->getData());
            }
            $this->requestResource->save($request);
            $requestItemIds = [];
            foreach ($request->getRequestItems() as $item) {
                $item->setRequestId($request->getRequestId());
                $this->requestItemResource->save($item);
                $requestItemIds[] = $item->getRequestItemId();
            }
            $this->requestItemResource->removeDeletedItems($request->getRequestId(), $requestItemIds);

            $origStatus = (int)$request->getOrigData(RequestInterface::STATUS);

            if ($origStatus !== 0 && $origStatus !== $request->getStatus()) {
                $this->eventManager->dispatch(
                    'return_request_status_changed',
                    ['request' => $request, 'original_status' => $origStatus, 'new_status' => $request->getStatus()]
                );
            }

            $origRefundStatus = (int)$request->getOrigData(RequestInterface::REFUND_STATUS);

            if ($origRefundStatus !== 0 && $origRefundStatus !== $request->getRefundStatus()) {
                $this->eventManager->dispatch(
                    'return_request_refund_status_changed',
                    [
                        'request' => $request,
                        'original_refund_status' => $origRefundStatus,
                        'new_refund_status' => $request->getRefundStatus()
                    ]
                );
            }

            unset($this->requests[$request->getRequestId()]);
        } catch (Exception $e) {
            if ($request->getRequestId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save request with ID %1. Error: %2',
                        [$request->getRequestId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new request. Error: %1', $e->getMessage()));
        }

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function getById($requestId)
    {
        if (!isset($this->requests[$requestId])) {
            /**
             * @var RequestInterface $request
             */
            $request = $this->requestFactory->create();
            $this->requestResource->load($request, $requestId);
            if (!$request->getRequestId()) {
                throw new NoSuchEntityException(__('Request with specified ID "%1" not found.', $requestId));
            }
            /**
             * @var ResourceModel\RequestItemCollection $requestItemCollection
             */
            $requestItemCollection = $this->requestItemCollectionFactory->create();
            $requestItemCollection->addFieldToFilter(
                RequestItemInterface::REQUEST_ID,
                $request->getRequestId()
            )->addOrder(RequestItemInterface::REQUEST_ITEM_ID, Collection::SORT_ORDER_ASC)
                ->addOrder(RequestItemInterface::ORDER_ITEM_ID, Collection::SORT_ORDER_ASC);
            $request->setRequestItems($requestItemCollection->getItems());
            /**
             * @var ResourceModel\TrackingCollection $trackingCollection
             */
            $trackingCollection = $this->trackingCollectionFactory->create();
            $trackingCollection->addFieldToFilter(
                RequestItemInterface::REQUEST_ID,
                $request->getRequestId()
            );
            $request->setTrackingNumbers($trackingCollection->getItems());

            $this->requests[$requestId] = $request;
        }

        return $this->requests[$requestId];
    }

    /**
     * @inheritDoc
     */
    public function getTrackingByTrackingNumber($trackingNumber)
    {
        /**
         * @var TrackingInterface $tracking
         */
        $tracking = $this->trackingFactory->create();
        $this->trackingResource->load($tracking, $trackingNumber, 'tracking_number');
        if (!$tracking->getTrackingId()) {
            throw new NoSuchEntityException(__(
                'Request with specified tracking number "%1" not found.',
                $trackingNumber
            ));
        }

        return $tracking;
    }

    /**
     * @inheritDoc
     */
    public function deleteTrackingById($trackingId)
    {
        $tracking = $this->getTrackingById($trackingId);
        $this->trackingResource->delete($tracking);

        $this->eventManager->dispatch(
            'manager_deleted_tracking_number_rma',
            ['tracking' => $tracking, 'request' => $this->getById($tracking->getRequestId())]
        );
    }

    /**
     * @inheritDoc
     */
    public function getTrackingById($trackingId)
    {
        /**
         * @var TrackingInterface $tracking
         */
        $tracking = $this->trackingFactory->create();
        $this->trackingResource->load($tracking, $trackingId);
        if (!$tracking->getTrackingId()) {
            throw new NoSuchEntityException(__('Request with specified ID "%1" not found.', $trackingId));
        }

        return $tracking;
    }

    /**
     * @inheritDoc
     */
    public function delete(RequestInterface $request)
    {
        try {
            $this->requestResource->delete($request);

            unset($this->requests[$request->getRequestId()]);
        } catch (Exception $e) {
            if ($request->getRequestId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove request with ID %1. Error: %2',
                        [$request->getRequestId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove request. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($requestId)
    {
        $requestModel = $this->getById($requestId);
        $this->delete($requestModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRequestModel()
    {
        return $this->requestFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRequestItemModel()
    {
        return $this->requestItemFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyTrackingModel()
    {
        return $this->trackingFactory->create();
    }
}
