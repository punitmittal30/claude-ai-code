<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Model\Resolver;

use Hyuga\Catalog\Model\Cache\ProductAttributeCache;
use Hyuga\LogManagement\Logger\GraphQlResolverLogger;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Catalog\Helper\Product as ProductHelper;

/**
 * Unified resolver for product attributes to optimize GraphQL performance
 */
class UnifiedProductAttributeResolver implements ResolverInterface
{
    /**
     * Attribute groups and their corresponding attribute codes
     */
    private const ATTRIBUTE_GROUPS = [
        'boolean' => [
            'is_hl_verified',
            'is_hm_verified',
            'deal_of_the_day'
        ],
        'date' => [
            'special_from_date_formatted',
            'special_to_date_formatted'
        ],
        'select' => [
            'form',
            'brand',
            'color',
            'dietary_preference',
            'flavour',
            'material',
            'pack_of',
            'pack_size',
            'primary_benefits',
            'size',
            'diet_type',
            'discount',
            'concern',
            'gender',
            'item_weight'
        ],
        'simple' => [
            'replenishment_time',
            'wp_product_id',
            'ean_code',
            'height',
            'length',
            'offers_title',
            'number_of_servings',
            'width'
        ],
        'price' => [
            'price_per_count',
            'price_per_100_ml',
            'price_per_100_gram',
            'price_per_gram_protein'
        ]
    ];

    /**
     * Date attribute mappings
     */
    private const DATE_ATTRIBUTES_MAP = [
        'special_from_date_formatted' => 'special_from_date',
        'special_to_date_formatted' => 'special_to_date',
    ];

    /**
     * Price attribute config paths
     */
    private const PRICE_ATTRIBUTE_CONFIG_MAP = [
        'price_per_count' => 'product/attribute_suffix/price_per_count',
        'price_per_100_ml' => 'product/attribute_suffix/price_per_100_ml',
        'price_per_100_gram' => 'product/attribute_suffix/price_per_100_gram',
        'price_per_gram_protein' => 'product/attribute_suffix/price_per_gram_protein'
    ];

    /**
     * Required attributes for price calculations
     */
    private const PRICE_REQUIRED_ATTRIBUTES = [
        'price_per_count' => ['total_number_of_count'],
        'price_per_100_ml' => ['total_volume_in_ml'],
        'price_per_100_gram' => ['number_of_serving_for_price_per_serving', 'protein_per_serving'],
        'price_per_gram_protein' => ['number_of_serving_for_price_per_serving', 'protein_per_serving']
    ];

    /**
     * Constructor
     *
     * @param ProductAttributeCache $attributeCache
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductHelper $productHelper
     * @param GraphQlResolverLogger $graphQlResolverLogger
     */
    public function __construct(
        private ProductAttributeCache $attributeCache,
        private TimezoneInterface $timezone,
        private ScopeConfigInterface $scopeConfig,
        private ProductHelper $productHelper,
        private GraphQlResolverLogger $graphQlResolverLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $product = $value['model'];
        $productId = $product->getId();
        $attributeCode = $field->getName();

        // Determine attribute group
        $attributeGroup = $this->getAttributeGroup($attributeCode);
        if (!$attributeGroup) {
            throw new LocalizedException(__('Attribute not supported by this resolver'));
        }

        // Handle different attribute groups
        return match ($attributeGroup) {
            'boolean' => $this->resolveBooleanAttribute($productId, $attributeCode),
            'date' => $this->resolveDateAttribute($productId, $attributeCode),
            'select' => $this->resolveSelectAttribute($productId, $attributeCode),
            'simple' => $this->resolveSimpleAttribute($productId, $attributeCode),
            'price' => $this->resolvePriceAttribute($product, $attributeCode),
            default => throw new LocalizedException(__('Attribute type not supported')),
        };
    }

    /**
     * Determine which group an attribute belongs to
     *
     * @param string $attributeCode
     * @return string|null
     */
    private function getAttributeGroup(string $attributeCode): ?string
    {
        foreach (self::ATTRIBUTE_GROUPS as $group => $attributes) {
            if (in_array($attributeCode, $attributes)) {
                return $group;
            }
        }
        return null;
    }

    /**
     * Resolve boolean attribute
     *
     * @param int $productId
     * @param string $attributeCode
     * @return int|bool
     */
    private function resolveBooleanAttribute(int $productId, string $attributeCode): int|bool
    {
        $value = $this->attributeCache->getAttribute($productId, $attributeCode);

        // Return appropriate type based on attribute
        if (in_array($attributeCode, ['is_hl_verified', 'is_hm_verified'])) {
            return $value === null ? 0 : (int)$value; // Return 0 or 1 as integers
        } else {
            return $value === null ? false : (bool)$value; // Return true or false
        }
    }

    /**
     * Resolve date attribute
     *
     * @param int $productId
     * @param string $attributeCode
     * @return string
     */
    private function resolveDateAttribute(int $productId, string $attributeCode): string
    {
        // Get the actual attribute code from the map
        $actualCode = self::DATE_ATTRIBUTES_MAP[$attributeCode] ?? null;
        if (!$actualCode) {
            return "";
        }

        $dateValue = $this->attributeCache->getAttribute($productId, $actualCode);

        if (!$dateValue) {
            return "";
        }

        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new \DateTime($dateValue), $locale)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->graphQlResolverLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Resolve select (EAV) attribute
     *
     * @param int $productId
     * @param string $attributeCode
     * @return string
     */
    private function resolveSelectAttribute(int $productId, string $attributeCode): string
    {
        // For select attributes, we want the text representation
        $textValue = $this->attributeCache->getAttribute($productId, $attributeCode . '_text');
        return $textValue ?? "";
    }

    /**
     * Resolve simple text attribute
     *
     * @param int $productId
     * @param string $attributeCode
     * @return string
     */
    private function resolveSimpleAttribute(int $productId, string $attributeCode): string
    {
        $value = $this->attributeCache->getAttribute($productId, $attributeCode);
        return $value ?? "";
    }

    /**
     * Resolve price calculation attribute
     *
     * @param ProductInterface $product
     * @param string $attributeCode
     * @return string
     */
    private function resolvePriceAttribute(ProductInterface $product, string $attributeCode): string
    {
        $productId = $product->getId();

        // For price attributes, we need to calculate them on-the-fly
        // Get required attributes from cache
        $attributes = [];
        $requiredAttrs = self::PRICE_REQUIRED_ATTRIBUTES[$attributeCode] ?? [];

        foreach ($requiredAttrs as $attr) {
            $attributes[$attr] = $this->attributeCache->getAttribute($productId, $attr);
        }

        // Calculate the price value
        $sellingPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        $value = $this->calculatePrice($attributeCode, $sellingPrice, $attributes);

        // Format and add suffix if needed
        if ($value > 0) {
            $configPath = self::PRICE_ATTRIBUTE_CONFIG_MAP[$attributeCode];
            $suffix = $this->productHelper->getConfig($configPath);
            return $suffix
                ? number_format($value, 1) . $suffix
                : number_format($value, 1);
        }

        return "";
    }

    /**
     * Calculate price based on attribute code and product attributes
     *
     * @param string $attributeCode
     * @param float $sellingPrice
     * @param array $attributes
     * @return float
     */
    private function calculatePrice(string $attributeCode, float $sellingPrice, array $attributes): float
    {
        try {
            switch ($attributeCode) {
                case 'price_per_count':
                    if (isset($attributes['total_number_of_count']) &&
                        is_numeric($attributes['total_number_of_count']) &&
                        (float)$attributes['total_number_of_count'] > 0
                    ) {
                        return $sellingPrice / (float)$attributes['total_number_of_count'];
                    }
                    break;

                case 'price_per_100_ml':
                    if (isset($attributes['total_volume_in_ml']) &&
                        is_numeric($attributes['total_volume_in_ml']) &&
                        (float)$attributes['total_volume_in_ml'] > 0
                    ) {
                        return ($sellingPrice / (float)$attributes['total_volume_in_ml']) * 100;
                    }
                    break;

                case 'price_per_100_gram':
                case 'price_per_gram_protein':
                    if (isset($attributes['number_of_serving_for_price_per_serving']) &&
                        is_numeric($attributes['number_of_serving_for_price_per_serving']) &&
                        (float)$attributes['number_of_serving_for_price_per_serving'] > 0 &&
                        isset($attributes['protein_per_serving']) &&
                        is_numeric($attributes['protein_per_serving']) &&
                        (float)$attributes['protein_per_serving'] > 0
                    ) {
                        $totalProtein = (float)$attributes['number_of_serving_for_price_per_serving'] *
                            (float)$attributes['protein_per_serving'];

                        if ($attributeCode === 'price_per_100_gram') {
                            return ($sellingPrice / $totalProtein) * 100;
                        } else {
                            return $sellingPrice / $totalProtein;
                        }
                    }
                    break;
            }
        } catch (\Exception $exception) {
            $this->graphQlResolverLogger->error($exception->getMessage() . __METHOD__);
        }

        return 0;
    }
}
