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

/**
 * Interface TrackingInterface
 */
interface TrackingInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const TRACKING_ID = 'tracking_id';
    public const REQUEST_ID = 'request_id';
    public const TRACKING_CODE = 'tracking_code';
    public const TRACKING_NUMBER = 'tracking_number';
    public const IS_CUSTOMER = 'is_customer';

    /**
     * @param int $trackingId
     *
     * @return $this
     */
    public function setTrackingId($trackingId);

    /**
     * @return int
     */
    public function getTrackingId();

    /**
     * @param int $requestId
     *
     * @return $this
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param string $trackingCode
     *
     * @return $this
     */
    public function setTrackingCode($trackingCode);

    /**
     * @return string
     */
    public function getTrackingCode();

    /**
     * @param string $trackingNumber
     *
     * @return $this
     */
    public function setTrackingNumber($trackingNumber);

    /**
     * @return string
     */
    public function getTrackingNumber();

    /**
     * @param bool $isCustomer
     *
     * @return $this
     */
    public function setIsCustomer($isCustomer);

    /**
     * @return bool
     */
    public function isCustomer();
}
