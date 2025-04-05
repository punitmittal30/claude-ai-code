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
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Service for formatting product data
 */
class ProductFormatterService
{
    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param CacheService $cacheService
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ProductAttributeRepositoryInterface $attributeRepository,
        private CacheService                        $cacheService,
        private ResourceConnection                  $resource,
        private LoggerInterface                     $logger
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
        return [
            '__typename' => $this->getProductType($product),
            'id' => (int)$product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'url_key' => $product->getUrlKey(),
            'image' => $this->getProductImage($product),
            'deal_of_the_day' => (bool)$product->getData('deal_of_the_day'),
            'badges' => $product->getData('badges') ?? '',
            'is_hl_verified' => (int)$product->getData('is_hl_verified') ?? 0,
            'is_hm_verified' => (int)$product->getData('is_hm_verified') ?? 0,
            'number_of_servings' => $product->getData('number_of_servings') ?? '',
            'star_ratings' => (int)$product->getData('star_ratings') ?? 0,
            'review_count' => (int)$product->getData('review_count') ?? 0,
            'price_per_count' => $product->getData('price_per_count') ?? '',
            'price_per_100_ml' => $product->getData('price_per_100_ml') ?? '',
            'price_per_100_gram' => $product->getData('price_per_100_gram') ?? '',
            'price_per_gram_protein' => $product->getData('price_per_gram_protein') ?? '',
            // Add attribute values and their labels
            'color' => $this->getAttributeOptionLabelByCode('color', $product->getData('color')),
            'dietary_preference' => $this->getAttributeOptionLabelByCode(
                'dietary_preference',
                $product->getData('dietary_preference')
            ),
            'material' => $this->getAttributeOptionLabelByCode(
                'material',
                $product->getData('material')
            ),
            'size' => $this->getAttributeOptionLabelByCode('size', $product->getData('size')),
            'flavour' => $this->getAttributeOptionLabelByCode('flavour', $product->getData('flavour')),
            'item_weight' => $this->getAttributeOptionLabelByCode(
                'item_weight',
                $product->getData('item_weight')
            ),
            'pack_of' => $this->getAttributeOptionLabelByCode('pack_of', $product->getData('pack_of')),
            'pack_size' => $this->getAttributeOptionLabelByCode(
                'pack_size',
                $product->getData('pack_size')
            )
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
     * Get attribute option label by code
     *
     * @param string $attributeCode
     * @param mixed $value
     * @return string
     */
    public function getAttributeOptionLabelByCode(string $attributeCode, $value): string
    {
        if (empty($value)) {
            return '';
        }

        $cacheKey = $this->cacheService->getAttributeOptionCacheKey($attributeCode, $value);
        $cachedLabel = $this->cacheService->get($cacheKey);

        if ($cachedLabel) {
            return $cachedLabel;
        }

        try {
            $attribute = $this->attributeRepository->get($attributeCode);
            $options = $attribute->getSource()->getAllOptions(false);

            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    $label = (string)$option['label'];

                    // Cache attribute option for a long time
                    $this->cacheService->save(
                        $cacheKey,
                        $label,
                        [CacheService::CACHE_TAG_STATIC],
                        CacheService::CACHE_LIFETIME_STATIC
                    );

                    return $label;
                }
            }

            // For multiselect attributes
            if (is_string($value) && strpos($value, ',') !== false) {
                $valueArray = explode(',', $value);
                $labels = [];

                foreach ($valueArray as $singleValue) {
                    foreach ($options as $option) {
                        if ($option['value'] == $singleValue) {
                            $labels[] = (string)$option['label'];
                            break;
                        }
                    }
                }

                if (!empty($labels)) {
                    $label = implode(', ', $labels);

                    // Cache multiselect attribute option for a long time
                    $this->cacheService->save(
                        $cacheKey,
                        $label,
                        [CacheService::CACHE_TAG_STATIC],
                        CacheService::CACHE_LIFETIME_STATIC
                    );

                    return $label;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error getting attribute option label: ' . $e->getMessage());
        }

        return (string)$value;
    }

    /**
     * Extract dynamic data that changes frequently
     *
     * @param Product $product
     * @return array
     */
    public function extractDynamicData(Product $product): array
    {
        // Get stock information
        $stockInfo = $this->getProductStockInfo($product->getId());

        return [
            'price' => (float)$product->getPrice(),
            'special_price' => (float)$product->getSpecialPrice(),
            'price_range' => $this->getPriceRange($product),
            'special_from_date_formatted' => $product->getSpecialFromDate() ?? '',
            'special_to_date_formatted' => $product->getSpecialToDate() ?? '',
            'stock_info' => [
                'qty' => $stockInfo['qty'] ?? 0,
                'min_sale_qty' => $stockInfo['min_sale_qty'] ?? 1,
                'max_sale_qty' => $stockInfo['max_sale_qty'] ?? 10000,
                'is_in_stock' => ($stockInfo['is_in_stock'] ?? false) && $product->getStatus() == 1
            ],
            'stock_status' => ($stockInfo['is_in_stock'] ?? false) ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'estimated_delivery_time' => [
                "warehouse_code" => '',
                "delivery_time" => 12,
                "quantity" => 0
            ]
        ];
    }

    /**
     * Get product stock info
     *
     * @param int $productId
     * @return array
     */
    public function getProductStockInfo(int $productId): array
    {
        $cacheKey = $this->cacheService->getStockInfoCacheKey($productId);
        $cachedStock = $this->cacheService->get($cacheKey);

        if ($cachedStock) {
            return $cachedStock;
        }

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

            $stockInfo = [
                'qty' => (float)$stockData['qty'],
                'is_in_stock' => (bool)$stockData['is_in_stock'],
                'min_sale_qty' => (float)$stockData['min_sale_qty'],
                'max_sale_qty' => (float)$stockData['max_sale_qty']
            ];

            // Cache stock info for a short time (5 minutes)
            $this->cacheService->save(
                $cacheKey,
                $stockInfo,
                [CacheService::CACHE_TAG_DYNAMIC],
                CacheService::CACHE_LIFETIME_DYNAMIC
            );

            return $stockInfo;
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
     * Merge static and dynamic data for products
     *
     * @param array $staticItems
     * @param array $dynamicItems
     * @return array
     */
    public function mergeStaticAndDynamicData(array $staticItems, array $dynamicItems): array
    {
        $mergedItems = [];

        foreach ($staticItems as $productId => $staticData) {
            if (isset($dynamicItems[$productId])) {
                $mergedItems[] = array_merge($staticData, $dynamicItems[$productId]);
            } else {
                // If dynamic data is missing, use static data with defaults
                $mergedItems[] = array_merge($staticData, [
                    'price' => 0,
                    'special_price' => 0,
                    'price_range' => [
                        'minimum_price' => [
                            'regular_price' => ['value' => 0],
                            'final_price' => ['value' => 0],
                            'discount' => ['amount_off' => 0, 'percent_off' => 0]
                        ]
                    ],
                    'stock_info' => [
                        'qty' => 0,
                        'min_sale_qty' => 1,
                        'max_sale_qty' => 10000,
                        'is_in_stock' => false
                    ],
                    'stock_status' => 'OUT_OF_STOCK',
                    'warehouse_quantity' => 0
                ]);
            }
        }

        return $mergedItems;
    }
}
