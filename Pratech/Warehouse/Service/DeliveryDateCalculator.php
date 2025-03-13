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

use DateInterval;
use DateTime;
use Magento\Framework\App\CacheInterface;
use Pratech\Warehouse\Helper\Config;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory\CollectionFactory as InventoryCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla\CollectionFactory as SlaCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\Warehouse\CollectionFactory as WarehouseCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\Warehouse as WarehouseResource;

class DeliveryDateCalculator
{
    private const CACHE_TAG = 'pratech_delivery_estimate';

    private const CACHE_LIFETIME = 3600; // 1 hour

    private const BATCH_SIZE = 50;

    /**
     * @param WarehouseCollectionFactory $warehouseCollectionFactory
     * @param SlaCollectionFactory $slaCollectionFactory
     * @param InventoryCollectionFactory $inventoryCollectionFactory
     * @param WarehouseResource $warehouseResource
     * @param CacheInterface $cache
     * @param Config $warehouseConfig
     */
    public function __construct(
        private WarehouseCollectionFactory $warehouseCollectionFactory,
        private SlaCollectionFactory       $slaCollectionFactory,
        private InventoryCollectionFactory $inventoryCollectionFactory,
        private WarehouseResource          $warehouseResource,
        private CacheInterface             $cache,
        private Config                     $warehouseConfig
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
        $cacheKey = $this->generateCacheKey($sku, $customerPincode);
        $cachedResult = $this->cache->load($cacheKey);

        if ($cachedResult) {
            return json_decode($cachedResult, true);
        }

        $result = $this->calculateDeliveryEstimate($sku, $customerPincode);

        if (!$result) {
            $dropshipDeliveryEta = (int)$this->warehouseConfig->getDropshipDeliveryEta();
            $result = [
                'warehouse_code' => '',
                'delivery_time' => $dropshipDeliveryEta,
                'quantity' => 0
            ];
        }

        $this->cache->save(
            json_encode($result),
            $cacheKey,
            [self::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        return $result;
    }

    /**
     * Generate cache key for delivery estimate
     *
     * @param string $sku
     * @param int $customerPincode
     * @return string
     */
    private function generateCacheKey(string $sku, int $customerPincode): string
    {
        return self::CACHE_TAG . '_' . $sku . '_' . $customerPincode;
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

        return null;
    }

    /**
     * Calculate the actual delivery date based on delivery time
     *
     * @param int $deliveryTime Hours
     * @return string
     */
    private function calculateDate(int $deliveryTime): string
    {
        $date = new DateTime();
        $date->add(new DateInterval("PT{$deliveryTime}H"));
        return $date->format('Y-m-d H:i:s');
    }
}
