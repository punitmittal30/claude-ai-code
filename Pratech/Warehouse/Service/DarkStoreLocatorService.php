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

namespace Pratech\Warehouse\Service;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for locating dark stores
 */
class DarkStoreLocatorService
{
    /**
     * @param ResourceConnection $resource
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param CacheService $cacheService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ResourceConnection           $resource,
        private WarehouseRepositoryInterface $warehouseRepository,
        private CacheService                 $cacheService,
        private LoggerInterface              $logger
    ) {
    }

    /**
     * Find nearest dark store for a pincode
     *
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function findNearestDarkStore(int $pincode): array
    {
        $cacheKey = $this->cacheService->getDarkStoreCacheKey($pincode);

        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        try {
            // Get all dark stores
            $darkStores = $this->warehouseRepository->getAvailableDarkStores();

            if (empty($darkStores)) {
                throw new NoSuchEntityException(__('No dark stores available'));
            }

            $warehousePincodes = array_column($darkStores, 'warehouse_pincode');

            // Find SLA data to determine nearest warehouse by delivery time
            $connection = $this->resource->getConnection();
            $slaTable = $this->resource->getTableName('pratech_warehouse_sla');

            $select = $connection->select()
                ->from($slaTable, ['warehouse_pincode', 'delivery_time', 'priority'])
                ->where('customer_pincode = ?', $pincode)
                ->where('warehouse_pincode IN (?)', $warehousePincodes)
                ->order('priority ASC')
                ->order('delivery_time ASC')
                ->limit(1);

            $slaData = $connection->fetchRow($select);

            $darkStore = null;

            if (!$slaData) {
                // No SLA data found, throw exception
                throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
            } else {
                // Get warehouse by pincode
                $warehousePincode = $slaData['warehouse_pincode'];

                foreach ($darkStores as $store) {
                    if ((int)$store['warehouse_pincode'] === (int)$warehousePincode) {
                        $darkStore = $store;
                        break;
                    }
                }

                // If no matching warehouse found, return first dark store
                if (!$darkStore) {
                    throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
                }
            }

            // Cache the result for a day since store locations rarely change
            $this->cacheService->save(
                $cacheKey,
                $darkStore,
                [CacheService::CACHE_TAG_DARK_STORE],
                CacheService::CACHE_LIFETIME_STATIC
            );

            return $darkStore;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Dark store not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error finding nearest dark store: ' . $e->getMessage());
            throw new NoSuchEntityException(__('No dark store available for pincode %1', $pincode));
        }
    }
}
