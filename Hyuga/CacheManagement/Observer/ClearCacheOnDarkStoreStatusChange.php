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

namespace Hyuga\CacheManagement\Observer;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Hyuga\CacheManagement\Model\Redis\WarehouseRedisCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ClearCacheOnDarkStoreStatusChange implements ObserverInterface
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param LoggerInterface $logger
     * @param WarehouseRedisCache $warehouseRedisCache
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private LoggerInterface       $logger,
        private WarehouseRedisCache   $warehouseRedisCache
    )
    {
    }

    /**
     * Clear cache when dark store status changes.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $object = $observer->getEvent()->getData();

            $eventName = $observer->getEvent()->getName();

            switch ($eventName) {
                case 'warehouse_entity_changed':
                    if ($object['old_value']['is_dark_store'] != $object['new_value']['is_dark_store']) {
                        $this->handleDarkStoreChange();
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->logger->error('Error clearing pincode cache in observer: ' . $e->getMessage());
        }
    }

    private function handleDarkStoreChange(): void
    {
        $this->cacheService->cleanAllPincodeCaches();
        $this->warehouseRedisCache->cleanAllPincodeCachesAndDarkStoreSlugs();
    }
}
