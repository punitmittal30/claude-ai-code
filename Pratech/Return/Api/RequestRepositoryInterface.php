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
namespace Pratech\Return\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Api\Data\TrackingInterface;

/**
 * Interface RequestRepositoryInterface
 */
interface RequestRepositoryInterface
{
    /**
     * @param int $requestId
     *
     * @return RequestInterface
     * @throws NoSuchEntityException
     */
    public function getById($requestId);

    /**
     * @param RequestInterface $request
     * @return RequestInterface
     * @throws CouldNotSaveException
     */
    public function save(RequestInterface $request);

    /**
     * @param TrackingInterface $tracking
     *
     * @return TrackingInterface
     * @throws CouldNotSaveException
     */
    public function saveTracking(TrackingInterface $tracking);

    /**
     * @param int $trackingId
     *
     * @return TrackingInterface
     * @throws NotFoundException
     */
    public function getTrackingById($trackingId);

    /**
     * @param int $trackingNumber
     *
     * @return TrackingInterface
     * @throws NotFoundException
     */
    public function getTrackingByTrackingNumber($trackingNumber);

    /**
     * @param int $trackingId
     *
     * @return TrackingInterface
     * @throws CouldNotDeleteException
     */
    public function deleteTrackingById($trackingId);

    /**
     * @param RequestInterface $request
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(RequestInterface $request);

    /**
     * @param int $requestId
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteById($requestId);

    /**
     * @return RequestInterface
     */
    public function getEmptyRequestModel();

    /**
     * @return RequestItemInterface
     */
    public function getEmptyRequestItemModel();

    /**
     * @return TrackingInterface
     */
    public function getEmptyTrackingModel();
}
