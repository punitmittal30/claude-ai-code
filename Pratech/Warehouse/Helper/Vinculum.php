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
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Warehouse\Model\ResourceModel\Warehouse;

class Vinculum
{
    /**
     * @param Curl $curl
     * @param Config $configHelper
     * @param Json $json
     * @param Warehouse $warehouseResource
     */
    public function __construct(
        private Curl      $curl,
        private Config    $configHelper,
        private Json      $json,
        private Warehouse $warehouseResource
    ) {
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
     * Is Vinculum Sync Enabled.
     *
     * @return bool
     */
    public function isVinculumSyncEnabled(): bool
    {
        return (bool)$this->configHelper->getConfig('warehouse/vinculum/enable');
    }
}
