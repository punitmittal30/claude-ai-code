<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Service;

use Exception;
use Hyuga\WondersoftIntegration\Helper\Config;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Hyuga\WondersoftIntegration\Model\PriceListData;
use Magento\Catalog\Api\Data\ProductInterface;
use Pratech\Catalog\Helper\Eav;

class ProductSyncService
{
    /**
     * ProductSyncService constructor.
     *
     * @param Config $config
     * @param Logger $logger
     * @param ApiService $apiService
     * @param PriceListData $priceListData
     * @param Eav $eavHelper
     */
    public function __construct(
        private Config        $config,
        private Logger        $logger,
        private ApiService    $apiService,
        private PriceListData $priceListData,
        private Eav           $eavHelper
    )
    {
    }

    /**
     * Sync product to eShopaid
     *
     * @param ProductInterface $product
     * @param int|null $stagingRowId
     * @return bool
     */
    public function syncProduct(ProductInterface $product, ?int $stagingRowId = null): bool
    {
        if (!$this->config->isEnabled()) {
            $this->logger->info('Product sync is disabled');
            return false;
        }

        try {
            $rowId = $stagingRowId ?? $product->getId();
            $productData = $this->prepareProductData($product, (int)$rowId);

            $this->logger->info('Syncing product: ' . $product->getSku());
            $result = $this->apiService->pushItem($productData);

            if (isset($result['Result']) && $result['Result'] === 'SUCCESS') {
                $this->logger->info('Product sync successful for SKU: ' . $product->getSku());
                return true;
            } else {
                $this->logger->error('Product sync failed for SKU: ' . $product->getSku());
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error('Error syncing product ' . $product->getSku() . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare product data for pushing to eShopaid
     *
     * @param ProductInterface $product
     * @param int $stagingRowId
     * @return array
     */
    public function prepareProductData(ProductInterface $product, int $stagingRowId): array
    {
        $productData = [
            'StagingRowID' => $stagingRowId,
            'ProductCode' => $product->getSku(),
            'ProductName' => $product->getName(),
            'ProductFullName' => $product->getName(),
            'Classification' => 0, // 0 for Product item
            'EANCode' => $product->getData('ean_code') ?? '',
            'SupplierCode' => '',
            'SupplierName' => '',
            'ManufacturerCode' => '',
            'ManufacturerName' => '',
            'UnitDescription' => '',
            'UOMCode' => '',
            'UOMDescription' => 'NOS',
            'BrandCode' => '',
            'BrandName' => $product->getCustomAttribute('brand')
                ? $this->eavHelper->getOptionLabel(
                    'brand',
                    $product->getCustomAttribute('brand')->getValue()
                ) : '',
            'CategoryCode' => '',
            'CategoryDescription' => '',
            'ChapterNumber' => $product->getCustomAttribute('hsn_code')
                ? $product->getCustomAttribute('hsn_code')->getValue()
                : 0,
            'TaxRate' => '',
            'PurchasePrice' => $product->getCost() ?? 0,
            'SalesPrice' => $product->getSpecialPrice() ?? 0,
            'MRP' => $product->getPrice(),
            'IsSerialNoProduct' => 1,
            'IsTaxInclusive' => 1,
            'IsBillable' => 1,
            'GVProduct' => 0,
            'AllowInIndent' => 1,
            'AllowInPurchase' => 1,
            'PurchaseUnit' => 1.000,
            'SalesUnit' => 1.000,
            'IsActive' => $product->getStatus(),
            'MOQ' => $product->getData('min_qty') ?? 0.000
        ];

        // Add attributes
//        $attributes = [];
//        $customAttributes = $product->getCustomAttributes();
//        if ($customAttributes) {
//            foreach ($customAttributes as $attribute) {
//                if (in_array($attribute->getAttributeCode(), ['weight', 'size', 'color'])) {
//                    $attributes[] = [
//                        'AttributeName' => $attribute->getAttributeCode(),
//                        'AttributeValue' => $attribute->getValue(),
//                        'AlternateCode' => ''
//                    ];
//                }
//            }
//        }

//        if (!empty($attributes)) {
//            $productData['Attributes'] = $attributes;
//        }

        // Add barcodes
        $barcodes = [];
        $ean = $product->getCustomAttribute('ean_code')
            ? $product->getCustomAttribute('ean_code')->getValue()
            : 0;
        if ($ean) {
            $barcodes[] = [
                'EANCode' => $ean,
                'IsActive' => 1
            ];
        }

        if (!empty($barcodes)) {
            $productData['Barcodes'] = $barcodes;
        }

        return $productData;
    }

    /**
     * Sync price list for product to eShopaid
     *
     * @param ProductInterface $product
     * @param string $priceListId
     * @param string $priceListName
     * @param float|null $price
     * @param string $customerGroupCode
     * @param string $storeCode
     * @return bool
     */
    public function syncProductPriceList(
        ProductInterface $product,
        string           $priceListId,
        string           $priceListName,
        ?float           $price = null,
        string           $customerGroupCode = 'DEFAULT',
        string           $storeCode = 'default'
    ): bool
    {
        if (!$this->config->isEnabled()) {
            $this->logger->info('Price list sync is disabled');
            return false;
        }

        try {
            $price = $price ?? $product->getPrice();
            $priceData = $this->priceListData->preparePriceListData(
                $priceListId,
                $priceListName,
                $product,
                $price,
                $customerGroupCode,
                $storeCode
            );

            $this->logger->info('Syncing price list for product: ' . $product->getSku());
            $result = $this->apiService->pushPriceList([$priceData]);

            if (isset($result['Result']) && $result['Result'] === 'SUCCESS') {
                $this->logger->info('Price list sync successful for SKU: ' . $product->getSku());
                return true;
            } else {
                $this->logger->error('Price list sync failed for SKU: ' . $product->getSku());
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error('Error syncing price list for product ' . $product->getSku() . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync multiple price lists for product to eShopaid
     *
     * @param ProductInterface $product
     * @param array $priceData [priceListId => [name => 'Name', price => 123.45, customerGroup => 'code']]
     * @param string $storeCode
     * @return bool
     */
    public function syncMultiplePriceLists(
        ProductInterface $product,
        array            $priceData,
        string           $storeCode = 'default'
    ): bool
    {
        if (!$this->config->isEnabled()) {
            $this->logger->info('Price list sync is disabled');
            return false;
        }

        try {
            $priceListsData = $this->priceListData->prepareMultiplePriceListData(
                $product,
                $priceData,
                $storeCode
            );

            if (empty($priceListsData)) {
                $this->logger->warning('No price list data prepared for product: ' . $product->getSku());
                return false;
            }

            $this->logger->info('Syncing multiple price lists for product: ' . $product->getSku());
            $result = $this->apiService->pushPriceList($priceListsData);

            if (isset($result['Result']) && $result['Result'] === 'SUCCESS') {
                $this->logger->info('Multiple price lists sync successful for SKU: ' . $product->getSku());
                return true;
            } else {
                $this->logger->error('Multiple price lists sync failed for SKU: ' . $product->getSku());
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error('Error syncing multiple price lists for product ' . $product->getSku() . ': ' . $e->getMessage());
            return false;
        }
    }
}
