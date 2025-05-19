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

use DateTime;
use Exception;
use Hyuga\Catalog\Service\GraphQlProductAttributeService;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ProductFormatterService
{
    public const ATTRIBUTE_SUFFIX_CONFIG_PATH = [
        'price_per_count' => 'product/attribute_suffix/price_per_count',
        'price_per_100_ml' => 'product/attribute_suffix/price_per_100_ml',
        'price_per_100_gram' => 'product/attribute_suffix/price_per_100_gram',
        'price_per_gram_protein' => 'product/attribute_suffix/price_per_gram_protein'
    ];

    /**
     * @param ResourceConnection $resource
     * @param GraphQlProductAttributeService $graphQlProductAttributeService
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezone
     * @param DeliveryDateCalculator $deliveryDateCalculator
     */
    public function __construct(
        private ResourceConnection             $resource,
        private GraphQlProductAttributeService $graphQlProductAttributeService,
        private LoggerInterface                $logger,
        private ScopeConfigInterface           $scopeConfig,
        private TimezoneInterface              $timezone,
        private DeliveryDateCalculator         $deliveryDateCalculator
    ) {
    }

    /**
     * Extract static data that rarely changes
     *
     * @param Product $product
     * @return array
     */
    public function extractStaticData(Product $product): array
    {
        $productId = (int)$product->getId();

        // Load only static attributes
        $staticAttributes = $this->graphQlProductAttributeService->getStaticAttributes($productId);

        // Extract static attributes
        return [
            '__typename' => $this->getProductType($product),
            'id' => $productId,
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'url_key' => $product->getUrlKey(),
            'image' => $this->getProductImage($product),
            'deal_of_the_day' => $staticAttributes['deal_of_the_day'] ?? false,
            'is_hl_verified' => (int)($staticAttributes['is_hl_verified'] ?? 0),
            'is_hm_verified' => (int)($staticAttributes['is_hm_verified'] ?? 0),
            'number_of_servings' => $staticAttributes['number_of_servings'] ?? '',
            'star_ratings' => $staticAttributes['rating_summary'] ?
                number_format(($staticAttributes['rating_summary'] / 20), 1)
                : 0,
            'review_count' => (int)($staticAttributes['review_count'] ?? 0),
            'rating_summary' => (int)($staticAttributes['rating_summary'] ?? 0),
            'badges' => $staticAttributes['badges_text'] ?? $staticAttributes['badges'] ?? '',
            'color' => $staticAttributes['color_text'] ?? '',
            'dietary_preference' => $staticAttributes['dietary_preference_text'] ?? '',
            'material' => $staticAttributes['material_text'] ?? '',
            'size' => $staticAttributes['size_text'] ?? '',
            'flavour' => $staticAttributes['flavour_text'] ?? '',
            'item_weight' => $staticAttributes['item_weight_text'] ?? '',
            'pack_of' => $staticAttributes['pack_of_text'] ?? '',
            'pack_size' => $staticAttributes['pack_size_text'] ?? ''
        ];
    }

    /**
     * Get product type for __typename
     *
     * @param Product $product
     * @return string
     */
    private function getProductType(Product $product): string
    {
        return match ($product->getTypeId()) {
            'configurable' => 'ConfigurableProduct',
            'bundle' => 'BundleProduct',
            'grouped' => 'GroupedProduct',
            default => 'SimpleProduct',
        };
    }

    /**
     * Get product image data
     *
     * @param Product $product
     * @return array
     */
    private function getProductImage(Product $product): array
    {
        $imageUrl = $product->getImage() ? $product->getMediaConfig()->getMediaUrl($product->getImage()) : '';
        return [
            'url' => $imageUrl,
            'label' => $product->getName()
        ];
    }

    /**
     * Extract dynamic data that changes frequently
     *
     * @param Product $product
     * @param int|null $pincode
     * @return array
     */
    public function extractDynamicData(Product $product, ?int $pincode): array
    {
        $productId = (int)$product->getId();

        // Get stock information
        $stockInfo = $this->getProductStockInfo($productId);
        $isDropship = (int)$product->getCustomAttribute('is_dropship')?->getValue();

        $inventoryQuantity = (int)$product->getData('inventory_quantity');
        $inventoryStockStatus = ($stockInfo['is_in_stock'] ?? false)
        && ($inventoryQuantity > 0)
        && ($product->getStatus() == 1)
            ? 'IN_STOCK'
            : 'OUT_OF_STOCK';

        // Get only dynamic attributes
        $dynamicAttributes = $this->graphQlProductAttributeService->getDynamicAttributes($productId);

        // Calculate the price-per attributes using pre-fetched data
        $pricePerAttributes = $this->calculatePricePerAttributes($product, $dynamicAttributes);

        return array_merge([
            'price' => (float)$product->getPrice(),
            'special_price' => (float)$product->getSpecialPrice(),
            'price_range' => $this->getPriceRange($product),
            'special_from_date_formatted' =>
                isset($dynamicAttributes['special_from_date']) && $dynamicAttributes['special_from_date'] != ""
                    ? $this->getDateTimeBasedOnTimezone($dynamicAttributes['special_from_date'])
                    : '',
            'special_to_date_formatted' =>
                isset($dynamicAttributes['special_to_date']) && $dynamicAttributes['special_to_date'] != ""
                    ? $this->getDateTimeBasedOnTimezone($dynamicAttributes['special_to_date'])
                    : '',
            'stock_info' => [
                'qty' => $stockInfo['qty'] ?? 0,
                'min_sale_qty' => $stockInfo['min_sale_qty'] ?? 1,
                'max_sale_qty' => $stockInfo['max_sale_qty'] ?? 10000,
                'is_in_stock' => ($stockInfo['is_in_stock'] ?? false) && $product->getStatus() == 1,
                'inventory_qty' => $inventoryQuantity
            ],
            'stock_status' => ($stockInfo['is_in_stock'] ?? false) ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'inventory_stock_status' => $inventoryStockStatus,
            'estimated_delivery_time' => $this->deliveryDateCalculator
                ->getEstimatedDelivery($product->getSku(), $pincode, $isDropship)
        ], $pricePerAttributes);
    }

    /**
     * Get product stock info
     *
     * @param int $productId
     * @return array
     */
    public function getProductStockInfo(int $productId): array
    {
        try {
            // Use direct SQL query for better performance
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(
                    ['cataloginventory_stock_item'],
                    ['qty', 'is_in_stock', 'min_sale_qty', 'max_sale_qty']
                )
                ->where('product_id = ?', $productId)
                ->where('stock_id = ?', 1);

            $stockData = $connection->fetchRow($select);

            if (!$stockData) {
                return [
                    'qty' => 0,
                    'is_in_stock' => false,
                    'min_sale_qty' => 1,
                    'max_sale_qty' => 10000
                ];
            }

            return [
                'qty' => (float)$stockData['qty'],
                'is_in_stock' => (bool)$stockData['is_in_stock'],
                'min_sale_qty' => (float)$stockData['min_sale_qty'],
                'max_sale_qty' => (float)$stockData['max_sale_qty']
            ];
        } catch (Exception $e) {
            $this->logger->error('Error loading stock item: ' . $e->getMessage());
            return [
                'qty' => 0,
                'is_in_stock' => false,
                'min_sale_qty' => 1,
                'max_sale_qty' => 10000
            ];
        }
    }

    /**
     * Calculate all price-per attributes dynamically
     *
     * @param Product $product
     * @param array $dynamicAttributes
     * @return array
     */
    private function calculatePricePerAttributes(Product $product, array $dynamicAttributes): array
    {
        $result = [
            'price_per_count' => '',
            'price_per_100_ml' => '',
            'price_per_100_gram' => '',
            'price_per_gram_protein' => ''
        ];

        try {
            // Get the current final price
            $finalPrice = (float)$product->getFinalPrice();

            // Calculate price_per_count using pre-fetched attributes
            $totalCount = $this->getNumericValue($dynamicAttributes, 'total_number_of_count');
            if ($totalCount > 0) {
                $result['price_per_count'] = $this->formatPrice('price_per_count', $finalPrice / $totalCount);
            }

            // Calculate price_per_100_ml
            $totalVolumeInMl = $this->getNumericValue($dynamicAttributes, 'total_volume_in_ml');
            if ($totalVolumeInMl > 0) {
                $result['price_per_100_ml'] = $this->formatPrice(
                    'price_per_100_ml',
                    ($finalPrice / $totalVolumeInMl) * 100
                );
            }

            // Calculate price_per_100_gram and price_per_gram_protein
            $numberOfServing = $this->getNumericValue($dynamicAttributes, 'number_of_serving_for_price_per_serving');
            $proteinPerServing = $this->getNumericValue($dynamicAttributes, 'protein_per_serving');

            if ($numberOfServing > 0 && $proteinPerServing > 0) {
                $pricePerGramProtein = $finalPrice / ($numberOfServing * $proteinPerServing);
                $result['price_per_gram_protein'] = $this->formatPrice('price_per_gram_protein', $pricePerGramProtein);
                $result['price_per_100_gram'] = $this->formatPrice('price_per_100_gram', $pricePerGramProtein * 100);
            }
        } catch (Exception $e) {
            $this->logger->error('Error calculating price-per attributes: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get numeric value safely from dynamic attributes
     *
     * @param array $dynamicAttributes
     * @param string $attributeCode
     * @return float
     */
    private function getNumericValue(array $dynamicAttributes, string $attributeCode): float
    {
        if (isset($dynamicAttributes[$attributeCode]) && is_numeric($dynamicAttributes[$attributeCode])) {
            return (float)$dynamicAttributes[$attributeCode];
        }
        return 0;
    }

    /**
     * Format price for consistent display
     *
     * @param string $attributeCode
     * @param float $price
     * @return string
     */
    private function formatPrice(string $attributeCode, float $price): string
    {
        $suffix = $this->getConfig(self::ATTRIBUTE_SUFFIX_CONFIG_PATH[$attributeCode]);
        return $suffix ? number_format($price, 1) . $suffix : number_format($price, 1);
    }

    /**
     * Get System Config
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get product price range data
     *
     * @param Product $product
     * @return array
     */
    public function getPriceRange(Product $product): array
    {
        $regularPrice = (float)$product->getPrice();
        $finalPrice = (float)$product->getFinalPrice();
        $discount = $regularPrice - $finalPrice;
        $percentOff = $regularPrice > 0 ? round(($discount / $regularPrice) * 100, 2) : 0;

        return [
            'minimum_price' => [
                'regular_price' => [
                    'value' => $regularPrice
                ],
                'final_price' => [
                    'value' => $finalPrice
                ],
                'discount' => [
                    'amount_off' => $discount,
                    'percent_off' => $percentOff
                ]
            ]
        ];
    }

    /**
     * Get Time Based On Timezone for Email
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public function getDateTimeBasedOnTimezone(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format($format);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }
}
