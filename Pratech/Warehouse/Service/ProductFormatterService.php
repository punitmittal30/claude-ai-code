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
use Hyuga\Catalog\Service\GraphQlProductAttributeService;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Service for formatting product data
 */
class ProductFormatterService
{
    /**
     * @param ResourceConnection $resource
     * @param GraphQlProductAttributeService $graphQlProductAttributeService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ResourceConnection             $resource,
        private GraphQlProductAttributeService $graphQlProductAttributeService,
        private LoggerInterface                $logger
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

        // Load all attributes from cache
        $allAttributes = $this->graphQlProductAttributeService->getAllAttributes($productId);

        // Extract static attributes
        return [
            '__typename' => $this->getProductType($product),
            'id' => $productId,
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'url_key' => $product->getUrlKey(),
            'image' => $this->getProductImage($product),
            'deal_of_the_day' => $allAttributes['deal_of_the_day'] ?? false,
            'badges' => $allAttributes['badges'] ?? '',
            'is_hl_verified' => (int)($allAttributes['is_hl_verified'] ?? 0),
            'is_hm_verified' => (int)($allAttributes['is_hm_verified'] ?? 0),
            'number_of_servings' => $allAttributes['number_of_servings'] ?? '',
            'star_ratings' => $allAttributes['rating_summary'] ?
                number_format(($allAttributes['rating_summary'] / 20), 1)
                : 0,
            'review_count' => (int)($allAttributes['review_count'] ?? 0),
            'price_per_count' => $allAttributes['price_per_count'] ?? '',
            'price_per_100_ml' => $allAttributes['price_per_100_ml'] ?? '',
            'price_per_100_gram' => $allAttributes['price_per_100_gram'] ?? '',
            'price_per_gram_protein' => $allAttributes['price_per_gram_protein'] ?? '',
            'color' => $allAttributes['color_text'] ?? '',
            'dietary_preference' => $allAttributes['dietary_preference_text'] ?? '',
            'material' => $allAttributes['material_text'] ?? '',
            'size' => $allAttributes['size_text'] ?? '',
            'flavour' => $allAttributes['flavour_text'] ?? '',
            'item_weight' => $allAttributes['item_weight_text'] ?? '',
            'pack_of' => $allAttributes['pack_of_text'] ?? '',
            'pack_size' => $allAttributes['pack_size_text'] ?? ''
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
     * @return array
     */
    public function extractDynamicData(Product $product): array
    {
        $productId = (int)$product->getId();

        // Get stock information
        $stockInfo = $this->getProductStockInfo($productId);

        // Get all cached attributes
        $allAttributes = $this->graphQlProductAttributeService->getAllAttributes($productId);

        return [
            'price' => (float)$product->getPrice(),
            'special_price' => (float)$product->getSpecialPrice(),
            'price_range' => $this->getPriceRange($product),
            'special_from_date_formatted' => $allAttributes['special_from_date'] ?? '',
            'special_to_date_formatted' => $allAttributes['special_to_date'] ?? '',
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
}
