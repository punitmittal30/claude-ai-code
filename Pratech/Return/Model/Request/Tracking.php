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

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\TrackingInterface;

class Tracking extends AbstractModel implements TrackingInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Tracking::class);
        $this->setIdFieldName(TrackingInterface::TRACKING_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingId($trackingId)
    {
        return $this->setData(TrackingInterface::TRACKING_ID, (int)$trackingId);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingId()
    {
        return (int)$this->_getData(TrackingInterface::TRACKING_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(TrackingInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(TrackingInterface::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingCode($trackingCode)
    {
        return $this->setData(TrackingInterface::TRACKING_CODE, $trackingCode);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingCode()
    {
        return $this->_getData(TrackingInterface::TRACKING_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setTrackingNumber($trackingNumber)
    {
        return $this->setData(TrackingInterface::TRACKING_NUMBER, $trackingNumber);
    }

    /**
     * @inheritdoc
     */
    public function getTrackingNumber()
    {
        return $this->_getData(TrackingInterface::TRACKING_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setIsCustomer($isCustomer)
    {
        return $this->setData(TrackingInterface::IS_CUSTOMER, (bool)$isCustomer);
    }

    /**
     * @inheritDoc
     */
    public function isCustomer()
    {
        return (bool)$this->_getData(TrackingInterface::IS_CUSTOMER);
    }
}
