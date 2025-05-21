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

namespace Pratech\Return\Model\History;

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\HistoryInterface;
use Pratech\Return\Model\History\ResourceModel\History as HistoryResourceModel;

class History extends AbstractModel implements HistoryInterface
{
    /**
     * History Model Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(HistoryResourceModel::class);
        $this->setIdFieldName(HistoryInterface::EVENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getEventId()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEventId($eventId)
    {
        return $this->setData(HistoryInterface::EVENT_ID, (int)$eventId);
    }

    /**
     * @inheritDoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(HistoryInterface::REQUEST_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(HistoryInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritDoc
     */
    public function getRequestStatusId()
    {
        return (int)$this->_getData(HistoryInterface::REQUEST_STATUS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRequestStatusId($requestStatusId)
    {
        return $this->setData(HistoryInterface::REQUEST_STATUS_ID, (int)$requestStatusId);
    }

    /**
     * @inheritDoc
     */
    public function getEventDate()
    {
        return $this->_getData(HistoryInterface::EVENT_DATE);
    }

    /**
     * @inheritDoc
     */
    public function getEventType()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setEventType($eventType)
    {
        return $this->setData(HistoryInterface::EVENT_TYPE, (int)$eventType);
    }

    /**
     * @inheritDoc
     */
    public function getEventData()
    {
        if ($data = $this->_getData(HistoryInterface::EVENT_DATA)) {
            $data = json_decode($data, true);
            if (!json_last_error()) {
                return $data;
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setEventData($data)
    {
        if (!is_array($data)) {
            $data = [];
        }

        return $this->setData(HistoryInterface::EVENT_DATA, json_encode($data));
    }

    /**
     * @inheritDoc
     */
    public function getEventInitiator()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_INITIATOR);
    }

    /**
     * @inheritDoc
     */
    public function setEventInitiator($initiator)
    {
        return $this->setData(HistoryInterface::EVENT_INITIATOR, (int)$initiator);
    }

    /**
     * @inheritDoc
     */
    public function getEventInitiatorName()
    {
        return $this->getData(HistoryInterface::EVENT_INITIATOR_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setEventInitiatorName($initiatorName)
    {
        return $this->setData(HistoryInterface::EVENT_INITIATOR_NAME, $initiatorName);
    }

    /**
     * @inheritDoc
     */
    public function setMessage($message)
    {
        return $this->setData(HistoryInterface::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->setData(HistoryInterface::MESSAGE);
    }
}
