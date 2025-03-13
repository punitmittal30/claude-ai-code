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

namespace Pratech\Warehouse\Helper;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Pratech\Warehouse\Model\ResourceModel\Warehouse;

class Vinculum
{
    public const MIN_DELIVERY_DATE = 2;
    public const MAX_DELIVERY_DATE = 5;

    public const WAREHOUSES_PINCODE_MAPPING = [
        'W02' => 124105,
        'W03' => 421302,
        'W04' => 560067
    ];

    /**
     * @param Curl $curl
     * @param JsonHelper $jsonHelper
     * @param Config $configHelper
     * @param DateTime $date
     * @param TimezoneInterface $timezone
     * @param Json $json
     * @param Warehouse $warehouseResource
     */
    public function __construct(
        private Curl              $curl,
        private JsonHelper        $jsonHelper,
        private Config            $configHelper,
        private DateTime          $date,
        private TimezoneInterface $timezone,
        private Json              $json,
        private Warehouse         $warehouseResource
    ) {
    }

//    /**
//     * Get Estimated Delivery Date Based on Pincode.
//     *
//     * @param string $sku
//     * @param int $pincode
//     * @return array
//     * @throws NoSuchEntityException
//     */
//    public function getEstimatedDeliveryDate(string $sku, int $pincode): array
//    {
//        $predictedSlaMin = self::MIN_DELIVERY_DATE;
//        $predictedSlaMax = self::MAX_DELIVERY_DATE;
//        $nearestWarehouseCode = $this->getNearestWarehouse($sku, $pincode);
//        if (isset(self::WAREHOUSES_PINCODE_MAPPING[$nearestWarehouseCode])) {
//            $response = $this->getClickPostEstimatedDeliveryDate(
//                self::WAREHOUSES_PINCODE_MAPPING[$nearestWarehouseCode],
//                $pincode
//            );
//            if (!empty($response)) {
//                if ($response["meta"]["status"] == 200) {
//                    $predictedSlaMin = $response["result"][0]["predicted_sla_min"] ?? self::MIN_DELIVERY_DATE;
//                    $predictedSlaMax = $response["result"][0]["predicted_sla_max"] ?? self::MAX_DELIVERY_DATE;
//                }
//            }
//        }
//        $date = $this->timezone->date()->format('Y-m-d');
//        return [
//            'predicted_sla_min' => $this->date->date(
//                'Y-m-d',
//                strtotime($date . " +" . $predictedSlaMin . "days")
//            ),
//            'predicted_sla_max' => $this->date->date(
//                'Y-m-d',
//                strtotime($date . " +" . $predictedSlaMax . "days")
//            ),
//        ];
//    }

//    /**
//     * Get nearest warehouse for the sku.
//     *
//     * @param string $sku
//     * @param int $pincode
//     * @return string|null
//     * @throws NoSuchEntityException
//     */
//    public function getNearestWarehouse(string $sku, int $pincode): ?string
//    {
//        $nearestPincode = [
//            'status' => 1,
//            'first_nearest' => 124105,
//            'second_nearest' => 421302,
//            'third_nearest' => 560067
//        ];
//        if ($nearestPincode) {
//            $warehouses = [
//                $nearestPincode['first_nearest'],
//                $nearestPincode['second_nearest'],
//                $nearestPincode['third_nearest']
//            ];
//            foreach ($warehouses as $warehouse) {
//                $localLevelInventory = $this->getLotLevelInventory($sku, $warehouse);
//                if ($localLevelInventory['responseCode'] == 0) {
//                    return $warehouse;
//                }
//            }
//        } else {
//            throw new NoSuchEntityException(__('Selected pincode not serviceable at the moment'));
//        }
//        throw new NoSuchEntityException(__('Sku not available at any warehouse'));
//    }

//    /**
//     * Get Lot Level Inventory From Vinculum.
//     *
//     * @param string $sku
//     * @param string $locationCode
//     * @return array
//     */
//    public function getLotLevelInventory(string $sku, string $locationCode): array
//    {
//        $requestBody = $this->jsonHelper->jsonEncode([
//            'skuCodes' => [$sku],
//            'buckets' => ['Good'],
//            'locCode' => $locationCode,
//            'extLocCode' => ''
//        ]);
//
//        $this->curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
//        $this->curl->addHeader('Authorization', 'Bearer ' . $this->configHelper->getVinculumBearerToken());
//
//        $postFields = [
//            'ApiOwner' => $this->configHelper->getVinculumApiOwner(),
//            'ApiKey' => $this->configHelper->getVinculumApiKey(),
//            'RequestBody' => $requestBody
//        ];
//
//        $this->curl->post(
//            $this->configHelper->getVinculumBaseUrl() . '/RestWS/api/eretail/v1/sku/getlotlevelinventory',
//            $postFields
//        );
//
//        $response = $this->curl->getBody();
//        return $this->jsonHelper->jsonDecode($response);
//    }

//    /**
//     * Get Estimated Delivery Date
//     *
//     * @param string $destination
//     * @param string $origin
//     * @return array
//     */
//    public function getClickPostEstimatedDeliveryDate(string $destination, string $origin = ""): array
//    {
//        if (empty($origin)) {
//            $origin = $this->configHelper->getClickpostOriginPincode();
//        }
//
//        $body[] = [
//            "pickup_pincode" => $origin,
//            "drop_pincode" => $destination
//        ];
//
//        $url = $this->configHelper->getClickpostEddApiUrl();
//
//        if (null != $url) {
//            $urlParams = [
//                "username" => $this->configHelper->getClickpostUsername(),
//                "key" => $this->configHelper->getClickpostKey()
//            ];
//
//            $apiUrl = $url . '?' . http_build_query($urlParams);
//
//            $this->curl->addHeader("Content-Type", "application/json");
//            $this->curl->post($apiUrl, json_encode($body));
//            return json_decode($this->curl->getBody(), true);
//        }
//        return [];
//    }

//    /**
//     * Get specific SKUs inventory
//     *
//     * @param array $skuCodes
//     * @param string $locationCode
//     * @return array
//     */
//    public function getInventoryForSkus(array $skuCodes, string $locationCode = 'W03'): array
//    {
//        try {
//            $response = $this->fetchWarehouseInventory($skuCodes, $locationCode);
//            $result = [];
//
//            if (!empty($response['response'])) {
//                foreach ($response['response'] as $item) {
//                    $result[$item['skuCode']] = (float)$item['qty'];
//                }
//            }
//
//            // Fill missing SKUs with 0 quantity
//            foreach ($skuCodes as $sku) {
//                if (!isset($result[$sku])) {
//                    $result[$sku] = 0;
//                }
//            }
//
//            return $result;
//        } catch (Exception $e) {
//            // Log error and return empty quantities
//            return array_fill_keys($skuCodes, 0);
//        }
//    }

    /**
     * Fetch warehouse inventory details.
     *
     * @param array $skuCodes
     * @param string $locationCode
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int $pageNumber
     * @return array
     * @throws LocalizedException
     */
    public function fetchWarehouseInventory(
        array  $skuCodes = [],
        string $locationCode = 'W03',
        string $fromDate = null,
        string $toDate = null,
        int    $pageNumber = 1
    ): array {
        try {
            $fromDate = $fromDate ?: date('d/m/Y') . ' 00:00:01';
            $toDate = $toDate ?: date('d/m/Y') . ' 23:59:59';

            // Prepare request data
            $requestData = [
                'skuCodes' => $skuCodes,
                'buckets' => ['good'],
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'pageNumber' => (string)$pageNumber,
                'reqType' => '',
                'locCode' => $locationCode
            ];

            $this->curl->addHeader('ApiOwner', $this->configHelper->getVinculumApiOwner());
            $this->curl->addHeader('ApiKey', $this->configHelper->getVinculumApiKey());
            $this->curl->addHeader('Content-Type', 'application/json');

            $url = $this->configHelper->getVinculumBaseUrl() . '/RestWS/api/eretail/v4/stock/getWhInventory';

            $this->curl->post($url, $this->json->serialize($requestData));

            $statusCode = $this->curl->getStatus();
            if ($statusCode !== 200) {
                throw new LocalizedException(
                    __('API request failed with status code: %1', $statusCode)
                );
            }

            $response = $this->json->unserialize($this->curl->getBody());

            if (!isset($response['responseCode']) || $response['responseCode'] !== 0) {
                throw new LocalizedException(
                    __('API request failed: %1', $response['responseMessage'] ?? 'Unknown error')
                );
            }

            return $response;
        } catch (Exception $e) {
            throw new LocalizedException(__('Failed to fetch inventory: %1', $e->getMessage()));
        }
    }

    /**
     * Get All Warehouses Inventory.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllWarehousesInventory(): array
    {
        $response = [];
        $warehouseCodes = $this->warehouseResource->getAllEnabledWarehouseCodes();
        foreach ($warehouseCodes as $warehouseCode) {
            $response[$warehouseCode] = $this->getCompleteWarehouseInventory($warehouseCode);
        }
        return $response;
    }

    /**
     * Get complete warehouse inventory
     *
     * @param string $locationCode
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     * @throws LocalizedException
     */
    public function getCompleteWarehouseInventory(
        string  $locationCode = 'W03',
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $allInventory = [];
        $pageNumber = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->fetchWarehouseInventory([], $locationCode, $fromDate, $toDate, $pageNumber);

            if (!empty($response['response'])) {
                foreach ($response['response'] as $item) {
                    $allInventory[$item['skuCode']] = (int)$item['qty'];
                }
            }

            $hasMore = (bool)($response['hasMore'] ?? false);
            $pageNumber++;
        }

        return $allInventory;
    }

    /**
     * Is Vinculum Sync Enabled.
     *
     * @return bool
     */
    public function isVinculumSyncEnabled(): bool
    {
        return (bool)$this->configHelper->getConfig('warehouse/vinculum/enable');
    }
}
