<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\Data\PincodeInterface;

interface PincodeRepositoryInterface
{
    /**
     * Save pincode.
     *
     * @param PincodeInterface $pincode
     * @return PincodeInterface
     */
    public function save(PincodeInterface $pincode): PincodeInterface;

    /**
     * Get pincode by ID
     *
     * @param int $entityId
     * @return PincodeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): PincodeInterface;

    /**
     * Get pincode by Code
     *
     * @param int $pincode
     * @return PincodeInterface
     */
    public function getByCode(int $pincode): PincodeInterface;

    /**
     * Retrieve pincode matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete pincode
     *
     * @param PincodeInterface $pincode
     * @return bool
     */
    public function delete(PincodeInterface $pincode): bool;

    /**
     * Delete pincode by ID
     *
     * @param int $entityId
     * @return bool
     */
    public function deleteById(int $entityId): bool;

    /**
     * Get Pincode Serviceability.
     *
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getPincodeServiceability(int $pincode): array;
}
