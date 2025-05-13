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
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Model\Status\ResourceModel\Collection;

/**
 * Interface StatusRepositoryInterface
 */
interface StatusRepositoryInterface
{
    /**
     * @param int $statusId
     *
     * @return StatusInterface
     * @throws NoSuchEntityException
     */
    public function getById($statusId);

    /**
     * @param StatusInterface $status
     *
     * @return StatusInterface
     * @throws CouldNotSaveException
     */
    public function save(StatusInterface $status);

    /**
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function clearDeleted();

    /**
     * @param StatusInterface $status
     *
     * @return bool true on success
     */
    public function delete(StatusInterface $status);

    /**
     * @param int $statusId
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteById($statusId);

    /**
     * @return int
     */
    public function getInitialStatusId();

    /**
     * @return int
     */
    public function getCancelStatusId();

    /**
     * @return StatusInterface
     */
    public function getEmptyStatusModel();

    /**
     * @return Collection
     */
    public function getEmptyStatusCollection();
}
