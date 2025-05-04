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
use Pratech\Warehouse\Api\Data\WarehouseSlaInterface;

interface WarehouseSlaRepositoryInterface
{
    /**
     * Save SLA
     *
     * @param WarehouseSlaInterface $sla
     * @return WarehouseSlaInterface
     */
    public function save(WarehouseSlaInterface $sla): WarehouseSlaInterface;

    /**
     * Get SLA by ID
     *
     * @param int $slaId
     * @return WarehouseSlaInterface
     */
    public function getById(int $slaId): WarehouseSlaInterface;

    /**
     * Get SLA by customer and warehouse pincode
     *
     * @param int $customerPincode
     * @param int $warehousePincode
     * @return WarehouseSlaInterface
     */
    public function getBySla(int $customerPincode, int $warehousePincode): WarehouseSlaInterface;

    /**
     * Get list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete SLA
     *
     * @param WarehouseSlaInterface $sla
     * @return bool
     */
    public function delete(WarehouseSlaInterface $sla): bool;

    /**
     * Delete by ID
     *
     * @param int $slaId
     * @return bool
     */
    public function deleteById(int $slaId): bool;

    /**
     * Get Earliest At By Pincode.
     *
     * @param int $customerPincode
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEarliestAtByPincode(int $customerPincode): string;
}
