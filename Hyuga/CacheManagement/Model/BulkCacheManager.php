<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Model;

use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages caching for bulk operations
 */
class BulkCacheManager
{
    /**
     * @var array
     */
    private array $affectedPincodes = [];

    /**
     * @var array
     */
    private array $affectedWarehouses = [];

    /**
     * @var bool
     */
    private bool $hasBulkOperationActive = false;

    /**
     * @param CacheServiceInterface $cacheService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private LoggerInterface       $logger
    ) {
    }

    /**
     * Start a bulk operation - caches won't be cleared immediately
     *
     * @return void
     */
    public function startBulkOperation(): void
    {
        $this->hasBulkOperationActive = true;
        $this->affectedPincodes = [];
        $this->affectedWarehouses = [];
        $this->logger->info('Bulk cache operation started');
    }

    /**
     * Register a pincode as affected during bulk operation
     *
     * @param int $pincode
     * @return void
     */
    public function registerAffectedPincode(int $pincode): void
    {
        if ($this->hasBulkOperationActive) {
            $this->affectedPincodes[$pincode] = true;
            $this->logger->debug("Registered affected pincode: {$pincode}");
        } else {
            // If no bulk operation is active, clear immediately
            $this->cacheService->cleanPincodeCache($pincode);
        }
    }

    /**
     * Register a warehouse as affected during bulk operation
     *
     * @param string $warehouseCode
     * @return void
     */
    public function registerAffectedWarehouse(string $warehouseCode): void
    {
        if ($this->hasBulkOperationActive) {
            $this->affectedWarehouses[$warehouseCode] = true;
            $this->logger->debug("Registered affected warehouse: {$warehouseCode}");
        } else {
            // If no bulk operation is active, clear immediately
            $this->cacheService->cleanWarehouseFiltersCache($warehouseCode);
        }
    }

    /**
     * End bulk operation and clear all affected caches
     *
     * @param bool $clearAll If true, clears all cache types regardless of affected items
     * @return void
     */
    public function endBulkOperation(bool $clearAll = false): void
    {
        if (!$this->hasBulkOperationActive) {
            return;
        }

        $this->logger->info('Ending bulk cache operation');

        if ($clearAll) {
            // Clear all cache types
            $this->cacheService->cleanAllPincodeCaches();
            $this->cacheService->cleanAllWarehouseFiltersCaches();
            $this->cacheService->cleanAllWarehouseProductsCaches();
            $this->cacheService->cleanAllDarkStoreCaches();

            $this->logger->info('Cleared all cache types after bulk operation');
        } else {
            // Only clear affected caches
            $pincodeCount = count($this->affectedPincodes);
            $warehouseCount = count($this->affectedWarehouses);

            if ($pincodeCount > 0) {
                if ($pincodeCount > 10) {
                    // If many pincodes affected, clear all
                    $this->cacheService->cleanAllPincodeCaches();
                    $this->logger->info("Cleared all pincode caches ({$pincodeCount} pincodes affected)");
                } else {
                    // Clear individual pincode caches
                    foreach (array_keys($this->affectedPincodes) as $pincode) {
                        $this->cacheService->cleanPincodeCache($pincode);
                    }
                    $this->logger->info("Cleared {$pincodeCount} individual pincode caches");
                }
            }

            if ($warehouseCount > 0) {
                if ($warehouseCount > 5) {
                    // If many warehouses affected, clear all
                    $this->cacheService->cleanAllWarehouseFiltersCaches();
                    $this->cacheService->cleanAllWarehouseProductsCaches();
                    $this->logger->info("Cleared all warehouse caches ({$warehouseCount} warehouses affected)");
                } else {
                    // Clear individual warehouse caches
                    foreach (array_keys($this->affectedWarehouses) as $warehouseCode) {
                        $this->cacheService->cleanWarehouseFiltersCache($warehouseCode);
                    }
                    $this->logger->info("Cleared {$warehouseCount} individual warehouse caches");
                }
            }
        }

        // Reset state
        $this->hasBulkOperationActive = false;
        $this->affectedPincodes = [];
        $this->affectedWarehouses = [];
    }

    /**
     * Check if a bulk operation is currently active
     *
     * @return bool
     */
    public function isBulkOperationActive(): bool
    {
        return $this->hasBulkOperationActive;
    }
}
