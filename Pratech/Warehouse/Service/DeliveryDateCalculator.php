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

declare(strict_types=1);

namespace Pratech\Warehouse\Service;

use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Helper\Config;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory\CollectionFactory as InventoryCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla\CollectionFactory as SlaCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\Warehouse as WarehouseResource;
use Psr\Log\LoggerInterface;

/**
 * Service for calculating delivery dates
 */
class DeliveryDateCalculator
{
    private const BATCH_SIZE = 50;

    /**
     * @param InventoryCollectionFactory $inventoryCollectionFactory
     * @param SlaCollectionFactory $slaCollectionFactory
     * @param WarehouseResource $warehouseResource
     * @param CacheService $cacheService
     * @param Config $warehouseConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private InventoryCollectionFactory $inventoryCollectionFactory,
        private SlaCollectionFactory $slaCollectionFactory,
        private WarehouseResource $warehouseResource,
        private CacheService $cacheService,
        private Config $warehouseConfig,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get estimated delivery date for a product
     *
     * @param string $sku
     * @param int|null $customerPincode
     * @return array|null
     */
    public function getEstimatedDelivery(string $sku, int $customerPincode = null): ?array
    {
        if ($customerPincode === null) {
            return null;
        }

        // Generate cache key for this request
        $cacheKey = "delivery_estimate_{$sku}_{$customerPincode}";
        $cachedResult = $this->cacheService->get($cacheKey);

        if ($cachedResult) {
            return $cachedResult;
        }

        $result = $this->calculateDeliveryEstimate($sku, $customerPincode);

        // If no specific warehouse available, use default dropship estimate
        if (!$result) {
            $dropshipDeliveryEta = (int)$this->warehouseConfig->getDropshipDeliveryEta();
            $result = [
                'warehouse_code' => '',
                'delivery_time' => $dropshipDeliveryEta,
                'quantity' => 0
            ];
        }

        // Cache the result
        $this->cacheService->save(
            $cacheKey,
            $result,
            [CacheService::CACHE_TAG_DYNAMIC],
            CacheService::CACHE_LIFETIME_DYNAMIC
        );

        return $result;
    }

    /**
     * Calculate delivery estimate using warehouse availability and SLA data
     *
     * @param string $sku
     * @param int $customerPincode
     * @return array|null
     */
    private function calculateDeliveryEstimate(string $sku, int $customerPincode): ?array
    {
        try {
            // Get all warehouses serving this pincode, ordered by priority
            $slaCollection = $this->slaCollectionFactory->create();
            $slaCollection->addFieldToFilter('customer_pincode', $customerPincode)
                ->setOrder('priority', 'ASC');

            if ($slaCollection->getSize() === 0) {
                return null; // Pincode not serviceable
            }

            // Get warehouse pincodes for inventory check
            $warehousePincodes = $slaCollection->getColumnValues('warehouse_pincode');
            $warehousesPincodeAndCode = $this->warehouseResource->getWarehousesPinCodeAndCode($warehousePincodes);

            $warehouseCodes = array_values($warehousesPincodeAndCode);

            // Check inventory availability
            $inventoryCollection = $this->inventoryCollectionFactory->create();
            $inventoryCollection->addFieldToFilter('warehouse_code', ['in' => $warehouseCodes])
                ->addFieldToFilter('sku', $sku)
                ->addFieldToFilter('quantity', ['gt' => 0]);

            if ($inventoryCollection->getSize() === 0) {
                return null; // No stock available
            }

            // Create lookup array for warehouse inventory
            $warehouseInventory = [];
            foreach ($inventoryCollection as $inventory) {
                $warehouseInventory[$inventory->getWarehouseCode()] = $inventory->getQuantity();
            }

            // Find first warehouse with available stock
            foreach ($slaCollection as $sla) {
                $warehousePincode = $sla->getWarehousePincode();
                if (isset($warehousesPincodeAndCode[$warehousePincode])) {
                    $warehouseCode = $warehousesPincodeAndCode[$warehousePincode];
                    if (isset($warehouseInventory[$warehouseCode])) {
                        return [
                            'warehouse_code' => $warehouseCode,
                            'delivery_time' => $sla->getDeliveryTime(),
                            'quantity' => $warehouseInventory[$warehouseCode]
                        ];
                    }
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error('Error calculating delivery estimate: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in delivery estimate: ' . $e->getMessage());
        }

        return null;
    }
}
