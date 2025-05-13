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

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Pratech\Return\Api\Data\RequestInterface;

class Request extends AbstractModel implements RequestInterface
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context          $context,
        Registry         $registry,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Construct.
     *
     * @return void
     */
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(ResourceModel\Request::class);
        $this->setIdFieldName(RequestInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(RequestInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(RequestInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(RequestInterface::ORDER_ID, (int)$orderId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return (int)$this->_getData(RequestInterface::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setShipmentId($shipmentId)
    {
        return $this->setData(RequestInterface::SHIPMENT_ID, (int)$shipmentId);
    }

    /**
     * @inheritdoc
     */
    public function getShipmentId()
    {
        return (int)$this->_getData(RequestInterface::SHIPMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt)
    {
        $this->setData(RequestInterface::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->_getData(RequestInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setModifiedAt(string $modifiedAt)
    {
        $this->setData(RequestInterface::MODIFIED_AT, $modifiedAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getModifiedAt()
    {
        return $this->_getData(RequestInterface::MODIFIED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(RequestInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(RequestInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setRefundStatus($refundStatus)
    {
        return $this->setData(RequestInterface::REFUND_STATUS, (int)$refundStatus);
    }

    /**
     * @inheritdoc
     */
    public function getRefundStatus()
    {
        return (int)$this->_getData(RequestInterface::REFUND_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setIsProcessed($isProcessed)
    {
        return $this->setData(RequestInterface::IS_PROCESSED, (int)$isProcessed);
    }

    /**
     * @inheritdoc
     */
    public function getIsProcessed()
    {
        return (int)$this->_getData(RequestInterface::IS_PROCESSED);
    }

    /**
     * @inheritdoc
     */
    public function setInstantRefund($instantRefund)
    {
        return $this->setData(RequestInterface::INSTANT_REFUND, (int)$instantRefund);
    }

    /**
     * @inheritdoc
     */
    public function getInstantRefund()
    {
        return (int)$this->_getData(RequestInterface::INSTANT_REFUND);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(RequestInterface::CUSTOMER_ID, (int)$customerId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return (int)$this->_getData(RequestInterface::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName($customerName)
    {
        return $this->setData(RequestInterface::CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName()
    {
        return $this->_getData(RequestInterface::CUSTOMER_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setManagerId($managerId)
    {
        return $this->setData(RequestInterface::MANAGER_ID, (int)$managerId);
    }

    /**
     * @inheritdoc
     */
    public function getManagerId()
    {
        return (int)$this->_getData(RequestInterface::MANAGER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRating($rating)
    {
        return $this->setData(RequestInterface::RATING, (int)$rating);
    }

    /**
     * @inheritdoc
     */
    public function getRating()
    {
        return (int)$this->_getData(RequestInterface::RATING);
    }

    /**
     * @inheritdoc
     */
    public function setRatingComment($ratingComment)
    {
        return $this->setData(RequestInterface::RATING_COMMENT, $ratingComment);
    }

    /**
     * @inheritdoc
     */
    public function getRatingComment()
    {
        return $this->_getData(RequestInterface::RATING_COMMENT);
    }

    /**
     * @inheritDoc
     */
    public function setNote($note)
    {
        return $this->setData(RequestInterface::NOTE, $note);
    }

    /**
     * @inheritDoc
     */
    public function getNote()
    {
        return $this->_getData(RequestInterface::NOTE);
    }

    /**
     * @inheritDoc
     */
    public function setRequestItems($requestItems)
    {
        return $this->setData(RequestInterface::REQUEST_ITEMS, $requestItems);
    }

    /**
     * @inheritDoc
     */
    public function getRequestItems()
    {
        if ($items = $this->_getData(RequestInterface::REQUEST_ITEMS)) {
            return $items;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setTrackingNumbers($trackingNumbers)
    {
        return $this->setData(RequestInterface::TRACKING_NUMBERS, $trackingNumbers);
    }

    /**
     * @inheritDoc
     */
    public function getTrackingNumbers()
    {
        if ($items = $this->_getData(RequestInterface::TRACKING_NUMBERS)) {
            return $items;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setVinReturnNumber($vinReturnNumber)
    {
        return $this->setData(RequestInterface::VIN_RETURN_NUMBER, $vinReturnNumber);
    }

    /**
     * @inheritDoc
     */
    public function getVinReturnNumber()
    {
        if ($items = $this->_getData(RequestInterface::VIN_RETURN_NUMBER)) {
            return $items;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setRefundedAmount($refundedAmount)
    {
        return $this->setData(RequestInterface::REFUNDED_AMOUNT, $refundedAmount);
    }

    /**
     * @inheritDoc
     */
    public function getRefundedAmount()
    {
        return $this->_getData(RequestInterface::REFUNDED_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setRefundedStoreCredit($refundedStoreCredit)
    {
        return $this->setData(RequestInterface::REFUNDED_STORE_CREDIT, $refundedStoreCredit);
    }

    /**
     * @inheritDoc
     */
    public function getRefundedStoreCredit()
    {
        return $this->_getData(RequestInterface::REFUNDED_STORE_CREDIT);
    }

    /**
     * @inheritDoc
     */
    public function setComment($comment)
    {
        return $this->setData(RequestInterface::COMMENT, $comment);
    }

    /**
     * @inheritDoc
     */
    public function getComment()
    {
        return $this->_getData(RequestInterface::COMMENT);
    }
}
