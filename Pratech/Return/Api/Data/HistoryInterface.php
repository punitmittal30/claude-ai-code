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
namespace Pratech\Return\Api\Data;

interface HistoryInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const EVENT_ID = 'event_id';
    public const REQUEST_ID = 'request_id';
    public const REQUEST_STATUS_ID = 'request_status_id';
    public const EVENT_DATE = 'event_date';
    public const EVENT_TYPE = 'event_type';
    public const EVENT_DATA = 'event_data';
    public const EVENT_INITIATOR = 'event_initiator';
    public const EVENT_INITIATOR_NAME = 'event_initiator_name';
    public const MESSAGE = 'message';

    /**
     * @return int
     */
    public function getEventId();

    /**
     * @param int $eventId
     *
     * @return $this
     */
    public function setEventId($eventId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param int $requestId
     *
     * @return $this
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestStatusId();

    /**
     * @param int $requestStatusId
     *
     * @return $this
     */
    public function setRequestStatusId($requestStatusId);

    /**
     * @return string
     */
    public function getEventDate();

    /**
     * @return int
     */
    public function getEventType();

    /**
     * @param int $eventType
     *
     * @return $this
     */
    public function setEventType($eventType);

    /**
     * @return array
     */
    public function getEventData();

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setEventData($data);

    /**
     * @return int
     */
    public function getEventInitiator();

    /**
     * @param int $initiator
     *
     * @return $this
     */
    public function setEventInitiator($initiator);

    /**
     * @return string
     */
    public function getEventInitiatorName();

    /**
     * @param string $initiatorName
     *
     * @return $this
     */
    public function setEventInitiatorName($initiatorName);

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getMessage();
}
