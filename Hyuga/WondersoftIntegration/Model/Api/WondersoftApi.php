<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Model\Api;

use Exception;
use Hyuga\WondersoftIntegration\Api\WondersoftApiInterface;
use Hyuga\WondersoftIntegration\Helper\Data as Helper;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Catalog\Helper\Eav;

class WondersoftApi implements WondersoftApiInterface
{
    /**
     * @var string|null
     */
    protected $token = null;

    /**
     * @var int
     */
    protected $tokenExpiry = 0;

    /**
     * Constructor
     *
     * @param Helper $helper
     * @param Logger $logger
     * @param Curl $curl
     * @param Json $json
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Eav $eavHelper
     */
    public function __construct(
        private Helper                      $helper,
        private Logger                      $logger,
        private Curl                        $curl,
        private Json                        $json,
        private CategoryRepositoryInterface $categoryRepository,
        private Eav                         $eavHelper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function pushProduct($product): bool
    {
        if (!$this->helper->isProductPushEnabled()) {
            return false;
        }

        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $url = $this->helper->getApiBaseUrl() . '/ProcessData';

        $this->curl->addHeader('SERVICE_METHODNAME', 'PushItem');
        $this->curl->addHeader('AUTHORIZATION', $token);
        $this->curl->addHeader('Content-Type', 'application/json');

        $requestData = $this->prepareProductData($product);

        try {
            $this->logger->info('Pushing product to Wondersoft: ' . $product->getSku());
            $this->logger->info('Request: ' . $this->json->serialize($requestData));

            $this->curl->post($url, $this->json->serialize($requestData));

            $response = $this->curl->getBody();

            // Parse JSON response
            $responseData = $this->json->unserialize($response);

            if (isset($responseData['Response']['Result']) && $responseData['Response']['Result'] === 'SUCCESS') {

                $this->logger->info(
                    'Product pushed successfully: ' . $product->getSku() . ' - Response: ' . $response
                );
                return true;
            } else {
                $this->logger->error(
                    'Failed to push product: ' . $product->getSku() . ' - Response: ' . $response
                );
                return false;
            }
        } catch (Exception $e) {
            $this->logger->critical('Exception when pushing product: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function getToken(): string
    {
        // Check if token exists and is still valid
        if ($this->token !== null && $this->tokenExpiry > time()) {
            return $this->token;
        }

        $url = $this->helper->getApiBaseUrl() . '/token';

        // Add required headers exactly as shown in Postman collection
        $this->curl->addHeader('SERVICE_METHODNAME', 'GETTOKEN');
        $this->curl->addHeader('USERNAME', $this->helper->getApiUsername());
        $this->curl->addHeader('PASSWORD', $this->helper->getApiPassword());

        try {
            // Use empty array for POST body as per Postman
            $this->curl->post($url, []);

            $statusCode = $this->curl->getStatus();
            $response = $this->curl->getBody();

            $this->logger->info('Token API response status: ' . $statusCode);
            $this->logger->info('Token API response body: ' . $response);

            // Parse JSON response
            $responseData = $this->json->unserialize($response);

            if (isset($responseData['Response']['Access_Token'])
                && isset($responseData['Response']['Result'])
                && $statusCode == 200
                && $responseData['Response']['Result'] === 'SUCCESS'
            ) {
                $this->token = $responseData['Response']['Access_Token'];
                // Set token expiry (token lifetime is 30 minutes)
                $this->tokenExpiry = time() + ((int)$this->helper->getTokenLifetime() * 60);

                $this->logger->info('Token retrieved successfully');
                return $this->token;
            }

            $this->logger->error('Failed to parse token from response: ' . $response);
            return false;
        } catch (Exception $e) {
            $this->logger->critical('Exception when getting token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare product data for API request
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function prepareProductData(ProductInterface $product): array
    {
        $sku = $product->getSku();
        $name = $product->getName();

        // Determine which price to use
        $price = $product->getPrice();

        // Use special price if active
        if ($this->isSpecialPriceActive($product)) {
            $price = $product->getSpecialPrice();
        }

        $categoryId = $product->getCustomAttribute('primary_l1_category') ?
            $product->getCustomAttribute('primary_l1_category')->getValue() : 3;

        $categoryName = $this->getCategoryName($categoryId);

        $eanCode = $product->getCustomAttribute('ean_code')
            ? $product->getCustomAttribute('ean_code')->getValue()
            : '';

        $hsnCode = $product->getCustomAttribute('hsn_code')
            ? $product->getCustomAttribute('hsn_code')->getValue()
            : '';

        return [
            "MasterItem" => [
                "Item" => [
                    "StagingRowID" => 1,
                    "ProductCode" => $sku,
                    "ProductName" => (strlen($name) <= 150) ? $name : substr($name, 0, 150),
                    "ProductFullName" => $name,
                    "Classification" => 0,
                    "EANCode" => $eanCode,
                    "SupplierCode" => '',
                    "SupplierName" => '',
                    "ManufacturerCode" => '',
                    "ManufacturerName" => '',
                    "UnitDescription" => '',
                    "UOMDescription" => '',
                    "BrandCode" => '',
                    "BrandName" => '',
                    "CategoryCode" => $categoryName,
                    "CategoryDescription" => $categoryName,
                    "SubCategoryCode" => '',
                    "SubCategoryDescription" => '',
                    "ChapterNumber" => $hsnCode,
                    "TaxRate" => $product->getCustomAttribute('gst')
                        ? (int)$this->eavHelper->getOptionLabel(
                            'gst',
                            $product->getCustomAttribute('gst')->getValue()
                        ) : 0,
                    "PurchasePrice" => $product->getFloorPrice() ?? 0,
                    "SalesPrice" => $price,
                    "MRP" => $product->getPrice(),
                    "IsSerialNoProduct" => 0,
                    "IsTaxInclusive" => 1,
                    "IsBillable" => 1,
                    "GVProduct" => 0,
                    "AllowInIndent" => 1,
                    "AllowInPurchase" => 1,
                    "PurchaseUnit" => 1,
                    "SalesUnit" => 1,
                    "IsActive" => $product->getStatus() == 1 ? 1 : 0,
                    "MOQ" => $product->getData('min_qty') ?? 1,
                    "IsBatchNumberCompulsory" => 0,
                    "IsMultiBatch" => 0,
                    "IsSerialNoMandatory" => 0,
                    "MaxLength4SerialNo" => 0,
                    "MinLength4SerialNo" => 0
                ]
            ]
        ];
    }

    /**
     * Determine if special price is currently active
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isSpecialPriceActive(ProductInterface $product): bool
    {
        $specialPrice = $product->getSpecialPrice();
        if (!$specialPrice) {
            return false;
        }

        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();

        // Current time in store timezone
        $now = time();

        // Check from date
        if ($specialFromDate) {
            // Convert to timestamps for proper comparison with time components
            $fromTimestamp = strtotime($specialFromDate);
            if ($fromTimestamp > $now) {
                return false; // Special price not started yet
            }
        }

        // Check to date
        if ($specialToDate) {
            // Convert to timestamps for proper comparison with time components
            $toTimestamp = strtotime($specialToDate);
            // Special price ends at end of day, so add 23:59:59
            if (!strpos($specialToDate, ':')) {
                $toTimestamp = strtotime($specialToDate . ' 23:59:59');
            }

            if ($toTimestamp < $now) {
                return false; // Special price already ended
            }
        }

        return true;
    }

    /**
     * Get Category Data.
     *
     * @param int $categoryId
     * @return string
     */
    public function getCategoryName(int $categoryId): string
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return $category->getName();
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return 'Default Category';
        }
    }

    /**
     * @inheritdoc
     */
    public function pushPriceList($product): bool
    {
        if (!$this->helper->isPricePushEnabled()) {
            return false;
        }

        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $url = $this->helper->getApiBaseUrl() . '/ProcessData';

        $this->curl->addHeader('SERVICE_METHODNAME', 'PushPriceList');
        $this->curl->addHeader('AUTHORIZATION', $token);
        $this->curl->addHeader('Content-Type', 'application/json');

        $requestData = $this->preparePriceListData($product);

        try {
            $this->logger->info('Pushing price list to Wondersoft: ' . $product->getSku());
            $this->logger->info('Request: ' . $this->json->serialize($requestData));

            $this->curl->post($url, $this->json->serialize($requestData));

            $response = $this->curl->getBody();

            // Parse JSON response
            $responseData = $this->json->unserialize($response);

            if (isset($responseData['Response']['Result']) && $responseData['Response']['Result'] === 'SUCCESS') {

                $this->logger->info(
                    'Price list pushed successfully: ' . $product->getSku() . ' - Response: ' . $response
                );
                return true;
            } else {
                $this->logger->error('Failed to push price list: ' . $product->getSku() . ' - Response: ' . $response);
                return false;
            }
        } catch (Exception $e) {
            $this->logger->critical('Exception when pushing price list: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare price list data for API request
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function preparePriceListData(ProductInterface $product): array
    {
        $sku = $product->getSku();
        $currentDate = date('Ymd');

        // Determine which price to use
        $price = $product->getPrice();

        // Use special price if active
        if ($this->isSpecialPriceActive($product)) {
            $price = $product->getSpecialPrice();
        }

        return [
            "PriceList" => [
                "Product" => [
                    [
                        "RowID" => "1",
                        "CompanyCode" => $this->helper->getCompanyCode(),
                        "Type" => "1",
                        "PriceListID" => $this->helper->getPriceListId(),
                        "PriceListName" => $this->helper->getPriceListName(),
                        "EffectiveFromDate" => $currentDate,
                        "ProductCode" => $sku,
                        "UOMCode" => "Pcs",
                        "Rate" => $price,
                        "IsActive" => 1,
                        "Attribute1" => "NA",
                        "Attribute2" => "NA",
                        "AlphaBatchID" => $sku . "-" . $currentDate
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function pushPriceRevision(array $products, string $revisionId, ?string $effectiveDate = null): bool
    {
        if (!$this->helper->isPriceRevisionPushEnabled()) {
            return false;
        }

        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $url = $this->helper->getApiBaseUrl() . '/ProcessData';

        $this->curl->addHeader('SERVICE_METHODNAME', 'PushPriceRevision');
        $this->curl->addHeader('AUTHORIZATION', $token);
        $this->curl->addHeader('Content-Type', 'application/json');

        $requestData = $this->preparePriceRevisionData($products, $revisionId, $effectiveDate);

        try {
            $this->logger->info('Pushing price revision to Wondersoft: ' . $revisionId);
            $this->logger->info('Request: ' . $this->json->serialize($requestData));

            $this->curl->post($url, $this->json->serialize($requestData));

            $response = $this->curl->getBody();

            // Parse JSON response
            $responseData = $this->json->unserialize($response);

            if (isset($responseData['Response']['Result']) && $responseData['Response']['Result'] === 'SUCCESS') {
                $this->logger->info('Price revision pushed successfully: ' . $revisionId . ' - Response: ' . $response);
                return true;
            } else {
                $this->logger->error('Failed to push price revision: ' . $revisionId . ' - Response: ' . $response);
                return false;
            }
        } catch (Exception $e) {
            $this->logger->critical('Exception when pushing price revision: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare price revision data for API request
     *
     * @param array $products
     * @param string $revisionId
     * @param string|null $effectiveDate
     * @return array
     */
    protected function preparePriceRevisionData(
        array $products,
        string $revisionId,
        ?string $effectiveDate = null
    ): array {
        // Use current date if not provided
        $effectiveDate = $effectiveDate ?? date('Y-m-d');

        $productItems = [];
        $lineNumber = 1;

        foreach ($products as $product) {
            $productItems[] = [
                "LineNumber" => $lineNumber++,
                "ProductCode" => $product['sku'],
                "SalesPrice" => $product['price'],
                "ItemCost" => $product['cost'] ?? 0,
                "MRP" => $product['mrp'] ?? $product['price'],
                "MSP" => $product['msp'] ?? "",
                "FromMRP" => $product['from_mrp'] ?? "",
                "QualityType" => $product['quality_type'] ?? 0,
                "AlphaBatchId" => $product['alpha_batch_id'] ?? "",
                "LotNumber" => $product['lot_number'] ?? "",
                "UOMCode" => $product['uom_code'] ?? "",
                "BarCode" => $product['barcode'] ?? ""
            ];
        }

        return [
            "PriceRevision" => [
                "Header" => [
                    "PriceRevisionID" => $revisionId,
                    "EffectiveFrom" => $effectiveDate
                ],
                "Products" => [
                    "Product" => $productItems
                ]
            ]
        ];
    }

    /**
     * Send a price revision for multiple products
     *
     * @param array $productData Array of product data with SKU and prices
     * @param string|null $revisionId Custom revision ID (optional)
     * @param string|null $effectiveDate Effective date (optional)
     * @return bool
     */
    public function sendBatchPriceRevision(
        array $productData,
        ?string $revisionId = null,
        ?string $effectiveDate = null
    ): bool {
        if (!$this->helper->isPriceRevisionPushEnabled()) {
            return false;
        }

        // Generate a revision ID if not provided
        $revisionId = $revisionId ?: $this->helper->generatePriceRevisionId();

        // Format the product data for the API
        $products = [];
        foreach ($productData as $item) {
            if (!isset($item['sku']) || !isset($item['price'])) {
                $this->logger->error('Missing required fields (sku or price) in price revision data');
                continue;
            }

            $products[] = $item;
        }

        if (empty($products)) {
            $this->logger->error('No valid products to include in price revision');
            return false;
        }

        return $this->pushPriceRevision($products, $revisionId, $effectiveDate);
    }
}
