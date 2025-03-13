<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Helper;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Helper for formatting product responses
 */
class ProductResponseFormatter extends AbstractHelper
{
    /**
     * List of attribute codes that should be transformed to labels
     *
     * @var array
     */
    private const ATTRIBUTES_TO_TRANSFORM = [
        'brand',
        'color',
        'dietary_preference',
        'flavour',
        'form',
        'gender',
        'item_weight',
        'material',
        'pack_of',
        'pack_size',
        'primary_benefits',
        'primary_l1_category',
        'primary_l2_category',
        'size',
    ];

    /**
     * @var array
     */
    private $attributeOptionsCache = [];

    /**
     * @param Context $context
     * @param Configurable $configurableType
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param StockRegistryInterface $stockItemRepository
     */
    public function __construct(
        Context                                     $context,
        private Configurable                        $configurableType,
        private DeliveryDateCalculator              $deliveryDateCalculator,
        private ProductAttributeRepositoryInterface $attributeRepository,
        private StockRegistryInterface              $stockItemRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Format product collection to standardized response format
     *
     * @param array $items
     * @return array
     */
    public function formatProductCollection(array $items): array
    {
        $formattedItems = [];

        foreach ($items as $product) {
            $formattedItems[] = $this->formatProduct($product);
        }

        return $formattedItems;
    }

    /**
     * Format a single product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function formatProduct(ProductInterface $product): array
    {
        $productStock = $this->getProductStockInfo($product->getId());
        $productData = [
            '__typename' => $this->getProductType($product),
            'is_hl_verified' => (int)$product->getData('is_hl_verified') ?? 0,
            'is_hm_verified' => (int)$product->getData('is_hm_verified') ?? 0,
            'id' => (int)$product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'url_key' => $product->getUrlKey(),
            'image' => $this->getProductImage($product),
            'estimated_delivery_time' => [
                "warehouse_code" => '',
                "delivery_time" => 12,
                "quantity" => 0
            ],
            'special_price' => (float)$product->getSpecialPrice(),
            'price_range' => $this->getPriceRange($product),
            'number_of_servings' => $product->getData('number_of_servings') ?? '',
            'primary_l2_category' => $product->getData('primary_l2_category'),
            'stock_info' => [
                'qty' => $productStock->getQty(),
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
                'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
            ],
            'price_per_count' => $product->getData('price_per_count') ?? '',
            'price_per_100_ml' => $product->getData('price_per_100_ml') ?? '',
            'price_per_100_gram' => $product->getData('price_per_100_gram') ?? '',
            'price_per_gram_protein' => $product->getData('price_per_gram_protein') ?? '',
            'offers' => $product->getData('offers'),
            'badges' => $product->getData('badges') ?? '',
            'deal_of_the_day' => (bool)$product->getData('deal_of_the_day'),
            'special_from_date_formatted' => $product->getSpecialFromDate() ?? '',
            'special_to_date_formatted' => $product->getSpecialToDate() ?? '',
            'usp' => $this->getProductUSP($product),
            'star_ratings' => (int)$product->getData('star_ratings') ?? 0,
            'review_count' => (int)$product->getData('review_count') ?? 0,
            'stock_status' => $productStock->getIsInStock() ? 'IN_STOCK' : 'OUT_OF_STOCK'
        ];

        // Add attribute values and their labels
        foreach (self::ATTRIBUTES_TO_TRANSFORM as $attributeCode) {
            $attributeValue = $product->getData($attributeCode);
            if ($attributeValue) {
                // Add both the original value and the label
                $productData[$attributeCode] = $this->getAttributeOptionLabelByCode(
                    $attributeCode,
                    $attributeValue
                );
            } else {
                $productData[$attributeCode] = '';
            }
        }

        // Add configurable product specific data
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $productData['default_variant_id'] = $this->getDefaultVariantId($product);
            $productData['configurable_options'] = $this->getConfigurableOptions($product);
            $productData['variants'] = $this->getProductVariants($product);
        }

        return $productData;
    }

    /**
     * Get Product Stock Info
     *
     * @param int $productId
     * @return StockItemInterface
     */
    public function getProductStockInfo(int $productId): StockItemInterface
    {
        return $this->stockItemRepository->getStockItem($productId);
    }

    /**
     * Get product type for __typename
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getProductType(ProductInterface $product): string
    {
        return match ($product->getTypeId()) {
            Configurable::TYPE_CODE => 'ConfigurableProduct',
            'bundle' => 'BundleProduct',
            'grouped' => 'GroupedProduct',
            default => 'SimpleProduct',
        };
    }

    /**
     * Get product image data
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getProductImage(ProductInterface $product): array
    {
        $imageUrl = $product->getImage() ? $product->getMediaConfig()->getMediaUrl($product->getImage()) : '';
        return [
            'url' => $imageUrl,
            'label' => $product->getName()
        ];
    }

    /**
     * Get product price range data
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getPriceRange(ProductInterface $product): array
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
     * Get product USP
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getProductUSP(ProductInterface $product): array
    {
        $usp = $product->getData('usp');

        if (is_string($usp)) {
            return explode(',', $usp);
        }

        return is_array($usp) ? $usp : [];
    }

    /**
     * Get attribute option label by attribute code and value
     *
     * @param string $attributeCode
     * @param mixed $value
     * @return string
     */
    private function getAttributeOptionLabelByCode(string $attributeCode, $value): string
    {
        try {
            // Check if we already have this option in cache
            $cacheKey = $attributeCode . '|' . $value;
            if (isset($this->attributeOptionsCache[$cacheKey])) {
                return $this->attributeOptionsCache[$cacheKey];
            }

            // Fetch attribute
            $attribute = $this->attributeRepository->get($attributeCode);

            // Get option label
            $label = $this->getAttributeOptionLabel($attribute, $value);

            // Cache the result
            $this->attributeOptionsCache[$cacheKey] = $label;

            return $label;
        } catch (Exception $e) {
            $this->_logger->error('Error getting attribute option label: ' . $e->getMessage());
            return (string)$value; // Return the value as string if label can't be found
        }
    }

    /**
     * Get attribute option label
     *
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string
     */
    private function getAttributeOptionLabel($attribute, $value): string
    {
        if (!$value) {
            return '';
        }

        try {
            $options = $attribute->getSource()->getAllOptions();

            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return (string)$option['label'];
                }
            }

            // For multiselect attributes (comma-separated values)
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
                    return implode(', ', $labels);
                }
            }
        } catch (Exception $e) {
            $this->_logger->error('Error getting option label: ' . $e->getMessage());
        }

        return (string)$value; // Return value as string if no label found
    }

    /**
     * Get default variant ID for configurable products
     *
     * @param ProductInterface $product
     * @return int|null
     */
    private function getDefaultVariantId(ProductInterface $product): ?int
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return null;
        }

        $childProducts = $product->getTypeInstance()->getUsedProducts($product);

        if (!empty($childProducts)) {
            $defaultProduct = reset($childProducts);
            return (int)$defaultProduct->getId();
        }

        return null;
    }

    /**
     * Get configurable options
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getConfigurableOptions(ProductInterface $product): array
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return [];
        }

        $options = [];
        $attributeOptions = $this->configurableType->getConfigurableAttributes($product);

        foreach ($attributeOptions as $attribute) {
            $options[] = [
                'label' => $attribute->getProductAttribute()->getStoreLabel()
            ];
        }

        return $options;
    }

    /**
     * Get product variants for configurable products
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getProductVariants(ProductInterface $product): array
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return [];
        }

        $variants = [];
        $childProducts = $product->getTypeInstance()->getUsedProducts($product);
        $configurableAttributes = $this->configurableType->getConfigurableAttributes($product);

        foreach ($childProducts as $childProduct) {
            $variantAttributes = [];

            foreach ($configurableAttributes as $attribute) {
                $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
                $attributeValue = $childProduct->getData($attributeCode);
                $attributeLabel = $this->getAttributeOptionLabel($attribute->getProductAttribute(), $attributeValue);

                $variantAttributes[] = [
                    'label' => $attributeLabel,
                    'code' => $attributeCode,
                    'value_index' => $attributeValue
                ];
            }

            $variants[] = [
                'product' => $this->formatProduct($childProduct),
                'attributes' => $variantAttributes
            ];
        }

        return $variants;
    }

    /**
     * Get L2 category data
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getL2Category(ProductInterface $product): array
    {
        $primaryL2Category = $product->getData('primary_l2_category');

        if ($primaryL2Category) {
            // Here you would typically load the category by ID
            // This is a simplified version
            return [
                'name' => $product->getData('l2_category_name') ?? null,
                'slug' => $product->getData('l2_category_slug') ?? null
            ];
        }

        return [
            'name' => null,
            'slug' => null
        ];
    }

    /**
     * Get stock info
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getStockInfo(ProductInterface $product): array
    {
        return [
            'min_sale_qty' => 1,
            'max_sale_qty' => 10000
        ];
    }

    /**
     * Get estimated delivery time
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getEstimatedDeliveryTime(ProductInterface $product): array
    {
        $deliveryTime = $product->getExtensionAttributes()->getEstimatedDeliveryTime();

        if ($deliveryTime) {
            return [
                'warehouse_code' => $deliveryTime->getWarehouseCode() ?? '',
                'delivery_time' => $deliveryTime->getDeliveryTime() ?? 144,
                'quantity' => $deliveryTime->getQuantity() ?? 0
            ];
        }

        return [
            'warehouse_code' => '',
            'delivery_time' => 144,
            'quantity' => 0
        ];
    }
}
