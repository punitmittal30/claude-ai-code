<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Helper;

use Exception;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Review\Model\RatingFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as SalesRuleCollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Helper\Data;
use Pratech\Base\Helper\Data as Helper;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Logger\RestApiLogger;
use Pratech\Cart\Model\Config\Source\PlatformUsed;
use Pratech\Cart\Model\Config\Source\RuleType;
use Pratech\Catalog\Model\AttributeMappingFactory;
use Pratech\Catalog\Model\CalculatePricePerAttributes;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct;
use Pratech\CustomDeliveryCharges\Helper\Data as CustomDeliveryHelper;
use Pratech\ReviewRatings\Model\ProductRatings;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Product helper class to provide data to catalog api endpoints.
 */
class Product
{
    /**
     * Product Entity Type
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * SYSTEM CONFIGURATION PATHS
     */
    public const CUSTOM_ATTRIBUTE_CONFIG_PATH = 'product/attributes/custom_attributes';
    public const CONFIGURABLE_ATTRIBUTE_CONFIG_PATH = 'product/attributes/configurable_attributes';
    public const CONTENT_ATTRIBUTE_CONFIG_PATH = 'product/attributes/content';
    public const PRODUCT_INFORMATION_CONFIG_PATH = 'product/attributes/product_information';
    public const ADDITIONAL_INFORMATION_CONFIG_PATH = 'product/attributes/additional_information';
    public const NO_OF_PRODUCTS_TO_SHOW_IN_CAROUSEL = 'product/general/no_of_products_in_carousel';
    public const COD_MAX_ORDER_TOTAL = 'payment/cashondelivery/max_order_total';
    public const COD_MIN_ORDER_TOTAL = 'payment/cashondelivery/min_order_total';
    public const MINIMUM_ORDER_VALUE = 'delivery/delivery_charges/minimum_order_value';
    public const DELIVERY_CHARGES = 'delivery/delivery_charges/amount';
    public const PREPAID_DISCOUNT_SLAB = 'prepaid_discount/general/ranges';
    public const BEST_DEALS_CATEGORY_ID = 'product/general/best_deals_category_id';
    public const ADDITIONAL_LABEL_CONFIG_PATH = 'product/general/additional_label';
    public const STORE_CREDIT_TITLE = 'store_credit/store_credit/title';
    public const STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH = 'store_credit/store_credit/store_credit_limit';
    public const CONVERSION_RATE_CONFIG_PATH = 'store_credit/store_credit/conversion_rate';
    public const DEFAULT_TOGGLE_BEHAVIOUR = 'store_credit/store_credit/default_toggle_behaviour';

    public const ATTRIBUTE_SUFFIX_CONFIG_PATH = [
        'price_per_count' => 'product/attribute_suffix/price_per_count',
        'price_per_100_ml' => 'product/attribute_suffix/price_per_100_ml',
        'price_per_100_gram' => 'product/attribute_suffix/price_per_100_gram',
        'price_per_gram_protein' => 'product/attribute_suffix/price_per_gram_protein'
    ];

    /**
     * ROOT CATEGORY ID
     */
    public const ROOT_CATEGORY_ID = 3;
    /**
     * @var array|string[]
     */
    public array $pricePerAttributesList = [
        'price_per_count',
        'price_per_100_ml',
        'price_per_100_gram',
        'price_per_gram_protein'
    ];
    /**
     * @var array
     */
    private array $categoryPath = [];

    /**
     * Product Helper Constructor
     *
     * @param StockRegistryInterface $stockItemRepository
     * @param Config $eavConfig
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRatings $productRatings
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurable
     * @param Data $swatchHelper
     * @param SearchInterface $search
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryManagementInterface $categoryManagement
     * @param Eav $eavHelper
     * @param Logger $logger
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param TimezoneInterface $timezoneInterface
     * @param Helper $helper
     * @param SalesRuleCollectionFactory $salesRuleCollectionFactory
     * @param ManagerInterface $eventManager
     * @param Action $productAction
     * @param PlatformUsed $platformUsed
     * @param CustomDeliveryHelper $customDeliveryHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductFactory $productFactory
     * @param RestApiLogger $restApiLogger
     * @param AttributeMappingFactory $attributeMappingFactory
     * @param CalculatePricePerAttributes $calculatePricePerAttributes
     * @param Attribute $attribute
     * @param RuleFactory $ruleFactory
     * @param RuleType $ruleType
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param LinkedProduct $linkedProductResource
     */
    public function __construct(
        private StockRegistryInterface               $stockItemRepository,
        private Config                               $eavConfig,
        private ProductRepositoryInterface           $productRepository,
        private CategoryRepositoryInterface          $categoryRepository,
        private ProductRatings                       $productRatings,
        private StoreManagerInterface                $storeManager,
        private Configurable                         $configurable,
        private Data                                 $swatchHelper,
        private SearchInterface                      $search,
        private CategoryCollectionFactory            $categoryCollectionFactory,
        private CategoryManagementInterface          $categoryManagement,
        private Eav                                  $eavHelper,
        private Logger                               $logger,
        private ConfigurableOptionsProviderInterface $configurableOptionsProvider,
        private TimezoneInterface                    $timezoneInterface,
        private Helper                               $helper,
        private SalesRuleCollectionFactory           $salesRuleCollectionFactory,
        private ManagerInterface                     $eventManager,
        private Action                               $productAction,
        private PlatformUsed                         $platformUsed,
        private CustomDeliveryHelper                 $customDeliveryHelper,
        private ScopeConfigInterface                 $scopeConfig,
        private ProductFactory                       $productFactory,
        private RestApiLogger                        $restApiLogger,
        private AttributeMappingFactory              $attributeMappingFactory,
        private CalculatePricePerAttributes          $calculatePricePerAttributes,
        private Attribute                            $attribute,
        private RuleFactory                          $ruleFactory,
        private RuleType                             $ruleType,
        private OrderCollectionFactory               $orderCollectionFactory,
        private ShipmentCollectionFactory            $shipmentCollectionFactory,
        private DeliveryDateCalculator               $deliveryDateCalculator,
        private LinkedProduct                        $linkedProductResource,
    ) {
    }

    /**
     * Quick Search API
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     * @throws NoSuchEntityException
     */
    public function quickSearch(SearchCriteriaInterface $searchCriteria): array
    {
        $products = [];
        $searchResult = $this->search->search($searchCriteria);
        foreach ($searchResult->getItems() as $item) {
            $products[] = $this->formatSearchResponse($item->getId());
        }
        return $products;
    }

    /**
     * Format Search Response
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    private function formatSearchResponse(int $productId): array
    {
        $product = $this->productRepository->getById($productId);
        return [
            "id" => $product->getId(),
            "sku" => $product->getSku(),
            "name" => $product->getName(),
            "image" => $product->getImage(),
            "slug" => $product->getUrlKey()
        ];
    }

    /**
     * Get products based on category id
     *
     * @param int $categoryId
     * @param int|null $pincode
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoryProductsById(int $categoryId, int $pincode = null): array
    {
        /** @var Category $category */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['eq' => $categoryId])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        }
        return $this->formatCategoryResponse($category, $pincode);
    }

    /**
     * Format Category Response
     *
     * @param Category $category
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatCategoryResponse(Category $category, int $pincode = null): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getUrlKey(),
            'product_count' => $category->getProductCount(),
            'products' => $this->getFormattedProductResponse(
                $category->getProductCollection()->addAttributeToSelect('*')
                    ->addAttributeToSort('position')->setPageSize($this->getNoOfProductsToShowInCarousel()),
                $pincode
            )
        ];
    }

    /**
     * Get Formatted Product Response
     *
     * @param Collection $productsCollection
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFormattedProductResponse(Collection $productsCollection, int $pincode = null): array
    {
        $products = [];
        foreach ($productsCollection as $product) {
            $products[] = $this->formatProductForCarousel($product->getId(), $pincode);
        }
        return $products;
    }

    /**
     * Format Product Response For Carousels
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatProductForCarousel(int $productId, int $pincode = null): array
    {
        $product = $this->productRepository->getById($productId);
        $productData = $this->getProductAttributes($product, $pincode);

        if ($product->getTypeId() == 'configurable') {
            $productData['configurable_product_options'] = $this->getConfigurableProductOptions($product, $pincode);
            $productData['price'] = $productData['configurable_product_options'][0]['values']['minimum_price'];
            if (isset($productData['configurable_product_options'][0]['values']['minimum_special_price'])) {
                $productData['special_price'] = $productData['configurable_product_options'][0]
                ['values']['minimum_special_price'];
            }
            $productData['default_variant_id'] = $this->getDefaultVariantId($product->getId());
            if (isset($productData['configurable_product_options'][0]['values']['parent_hl_verified'])) {
                $productData['is_hl_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hl_verified'];
            }
            if (isset($productData['configurable_product_options'][0]['values']['parent_hm_verified'])) {
                $productData['is_hm_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hm_verified'];
            }
        }

        $parentProductId = $this->getParentProductId($productId);

        if ($parentProductId) {
            $productData['parent_slug'] = $this->productRepository->getById($parentProductId)->getUrlKey();
        }

        return $productData;
    }

    /**
     * Get Product Attributes
     *
     * @param ProductInterface $product
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductAttributes(ProductInterface $product, int $pincode = null): array
    {
        $productStock = $this->getProductStockInfo($product->getId());
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'status' => $product->getStatus(),
            'sku' => $product->getSku(),
            'price' => $product->getPrice(),
            'type' => $product->getTypeId(),
            'slug' => $product->getCustomAttribute('url_key')->getValue(),
            'image' => $product->getImage(),
            'ratings' => $this->getAverageProductRating($product),
            'visibility' => $product->getVisibility(),
            'stock_info' => [
                'qty' => $productStock->getQty(),
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
                'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
            ],
            'dietary_preference' => $product->getCustomAttribute('dietary_preference')
                ? $this->eavHelper->getOptionLabel(
                    'dietary_preference',
                    $product->getCustomAttribute('dietary_preference')->getValue()
                ) : "",
            'item_weight' => $product->getCustomAttribute('item_weight')
                ? $this->eavHelper->getOptionLabel(
                    'item_weight',
                    $product->getCustomAttribute('item_weight')->getValue()
                ) : "",
            'flavour' => $product->getCustomAttribute('flavour')
                ? $this->eavHelper->getOptionLabel(
                    'flavour',
                    $product->getCustomAttribute('flavour')->getValue()
                ) : "",
            'badges' => $product->getCustomAttribute('badges')
                ? $this->eavHelper->getMultiselectOptionsLabel(
                    'badges',
                    $product->getCustomAttribute('badges')->getValue()
                ) : "",
            'deal_of_the_day' => $product->getCustomAttribute('deal_of_the_day')
                && $product->getCustomAttribute('deal_of_the_day')->getValue(),
            'number_of_servings' => $product->getCustomAttribute('number_of_servings') ?
                $product->getCustomAttribute('number_of_servings')->getValue() : "",
            'color' => $product->getCustomAttribute('color')
                ? $this->eavHelper->getOptionLabel(
                    'color',
                    $product->getCustomAttribute('color')->getValue()
                ) : "",
            'size' => $product->getCustomAttribute('size')
                ? $this->eavHelper->getOptionLabel(
                    'size',
                    $product->getCustomAttribute('size')->getValue()
                ) : "",
            'material' => $product->getCustomAttribute('material')
                ? $this->eavHelper->getOptionLabel(
                    'material',
                    $product->getCustomAttribute('material')->getValue()
                ) : ""
        ];

        if (!($pincode === null)) {
            $productData['estimated_delivery_time'] = $this->deliveryDateCalculator
                ->getEstimatedDelivery($product->getSku(), $pincode);
        }

        $productData = $this->appendConfigurableAttributes($product, $productData);

        if ($product->getCustomAttribute('primary_l1_category')) {
            $primaryL1CategoryId = $product->getCustomAttribute('primary_l1_category')->getValue();
            $productData['primary_l1_category'] = $this->getCategoryNameAndSlugById($primaryL1CategoryId);
        }
        if ($product->getCustomAttribute('primary_l2_category')) {
            $primaryL2CategoryId = $product->getCustomAttribute('primary_l2_category')->getValue();
            $productData['primary_l2_category'] = $this->getCategoryNameAndSlugById($primaryL2CategoryId);
        }

        if ($product->getCustomAttribute('is_hl_verified')) {
            $productData['is_hl_verified'] = $product->getCustomAttribute('is_hl_verified')->getValue();
        }

        if ($product->getCustomAttribute('is_hm_verified')) {
            $productData['is_hm_verified'] = $product->getCustomAttribute('is_hm_verified')->getValue();
        }

        if ($product->getCustomAttribute('special_price')) {
            $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
            $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                ? $this->helper->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_from_date')->getValue()
                )
                : "";
            $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                ? $this->helper->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_to_date')->getValue()
                )
                : "";
        }

        $productData['additional_label'] = [
            'website_wise' => $this->getConfig(self::ADDITIONAL_LABEL_CONFIG_PATH) ?? '',
            'product_wise' => $product->getCustomAttribute('additional_label')
                ? $product->getCustomAttribute('additional_label')->getValue()
                : ''
        ];

        foreach ($this->pricePerAttributesList as $attributeCode) {
            $attributeValue = $this->calculatePricePerAttributes->calculate($product, $attributeCode);
            if ($attributeValue) {
                $suffix = $this->getConfig(self::ATTRIBUTE_SUFFIX_CONFIG_PATH[$attributeCode]);

                $productData[$attributeCode] = $suffix
                    ? number_format($attributeValue, 1) . $suffix
                    : number_format($attributeValue, 1);
            }
        }

        return $productData;
    }

    /**
     * Append selected configurable attributes to product data response
     *
     * @param ProductInterface $product
     * @param array $productData
     * @return array
     */
    private function appendConfigurableAttributes(
        ProductInterface $product,
        array $productData
    ) {
        $configurableAttributesAdded = [
            'color', 'primary_l1_category', 'primary_l2_category', 'flavour', 'item_weight',
            'primary_benefits', 'dietary_preference', 'brand'
        ];
        $list = $this->scopeConfig->getValue(
            self::CONFIGURABLE_ATTRIBUTE_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
        $attributes = $list !== null ? explode(',', $list) : [];
        $attributesToAdd = array_diff($attributes, $configurableAttributesAdded);
        foreach ($attributesToAdd as $attributeCode) {
            $productData[$attributeCode] = $product->getCustomAttribute($attributeCode)
                ? $this->eavHelper->getOptionLabel(
                    $attributeCode,
                    $product->getCustomAttribute($attributeCode)->getValue()
                ) : "";
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
     * Get Average Rating of a Product
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAverageProductRating(ProductInterface $product): array
    {
        $childProductIds = $this->getChildProductIds($product);
        $productId = $product->getId();

        if (isset($childProductIds)) {
            return $this->getAverageRatingForChildProducts($childProductIds);
        } else {
            return $this->getProductRating($productId);
        }
    }

    /**
     * Get Child Product IDs for Configurable Products
     *
     * @param ProductInterface $product
     * @return array|null
     */
    private function getChildProductIds(ProductInterface $product): ?array
    {
        $configurableProductLinks = $product->getExtensionAttributes()->getConfigurableProductLinks();
        return $configurableProductLinks !== null ? array_values($configurableProductLinks) : null;
    }

    /**
     * Get Average Rating for Child Products
     *
     * @param array $childProductIds
     * @return array
     * @throws NoSuchEntityException
     */
    private function getAverageRatingForChildProducts(array $childProductIds): array
    {
        $totalRating = 0;
        $ratingCount = 0;

        foreach ($childProductIds as $childProductId) {
            $rating = $this->getProductRating($childProductId);
            if (isset($rating['count']) && isset($rating['stars'])) {
                $totalRating += ($rating['stars'] * $rating['count']);
                $ratingCount += $rating['count'];
            }
        }

        return [
            'count' => $ratingCount,
            'stars' => $ratingCount ? number_format(($totalRating / $ratingCount), 1) : 0
        ];
    }

    /**
     * Get Product Rating
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductRating(int $productId): array
    {
        $productRating = $this->productRatings->getProductRatings(
            $productId,
            $this->storeManager->getStore()->getId()
        );

        if ($productRating) {
            return [
                'count' => $productRating['reviews_count'],
                'stars' => number_format((floatval($productRating['rating_summary']) / 20), 1)
            ];
        }
        return [];
    }

    /**
     * Get Category Slug By ID
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryNameAndSlugById(int $categoryId): array
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return [
                'name' => $category->getName(),
                'slug' => $category->getUrlKey()
            ];
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
            return [];
        }
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
     * Get Configurable Product Options
     *
     * @param ProductInterface $product
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductOptions(ProductInterface $product, int $pincode = null): array
    {
        $configurableOptions = [];

        /** @var OptionInterface[] $options */
        $options = $product->getExtensionAttributes()->getConfigurableProductOptions();

        foreach ($options as $option) {
            $configurableOptions[] = [
                'attribute_id' => $option->getAttributeId(),
                'label' => $option->getLabel(),
                'position' => $option->getPosition(),
                'product_id' => $option->getProductId(),
                'attribute_code' => $this->attribute->load($option->getAttributeId())->getAttributeCode(),
                'values' => $this->getConfigurableProductVariants($option->getAttributeId(), $product, $pincode),
            ];
        }

        return $configurableOptions;
    }

    /**
     * Get Configurable Product Variants Data.
     *
     * @param string|null $optionId
     * @param ProductInterface $product
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductVariants(
        ?string $optionId,
        ProductInterface $product,
        int $pincode = null
    ): array {
        $variants = [];
        $minimumRegularPrice = $minimumSpecialPrice = 0;
        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $configurableOptions = $typeInstance->getConfigurableOptions($product);
        foreach ($configurableOptions as $key => $configurableOption) {
            if ($optionId == $key) {
                $configurableVariant = [];
                foreach ($configurableOption as $variant) {
                    $simpleProduct = $this->productRepository->get($variant['sku']);

                    $productStock = $this->getProductStockInfo($simpleProduct->getId());
                    $variant = array_merge($variant, $this->getProductAttributes($simpleProduct, $pincode));
                    $variant['option_id'] = $optionId;
                    $variant['product_id'] = $simpleProduct->getId();
                    $variant['stock_status'] = $productStock->getIsInStock() && $simpleProduct->getStatus() == 1;
                    $variant['media_gallery'] = $this->getMediaGallery($simpleProduct);

                    if (isset($variant['is_hl_verified']) && $variant['is_hl_verified'] == 1) {
                        $variants['parent_hl_verified'] = 1;
                    }
                    if (isset($variant['is_hm_verified']) && $variant['is_hm_verified'] == 1) {
                        $variants['parent_hm_verified'] = 1;
                    }

                    if ($variant['stock_status']) {
                        if ($minimumRegularPrice == 0 || $variant['price'] < $minimumRegularPrice) {
                            $minimumRegularPrice = $variant['price'];
                        }
                        if (isset($variant['special_price'])
                            && ($minimumSpecialPrice == 0 || $variant['special_price'] < $minimumSpecialPrice)
                        ) {
                            $minimumSpecialPrice = $variant['special_price'];
                        }
                    }
                    $configurableVariant[] = $variant;
                }
                $variants['minimum_price'] = $minimumRegularPrice;
                if ($minimumSpecialPrice != 0) {
                    $variants['minimum_special_price'] = $minimumSpecialPrice;
                }
                $variants['variants'] = $configurableVariant;
            }
        }
        return $variants;
    }

    /**
     * Get Product Media Gallery
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getMediaGallery(ProductInterface $product): array
    {
        $mediaGallery = [];
        foreach ($product->getMediaGalleryEntries() as $mediaGalleryEntry) {
            $mediaGallery[] = $mediaGalleryEntry->getData();
        }
        return $mediaGallery;
    }

    /**
     * Get Default Variant Id for Configurable Products
     *
     * @param int $productId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getDefaultVariantId(int $productId): int
    {
        $minimumAmount = null;
        $variantId = null;

        $product = $this->productRepository->getById($productId);

        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $productVariants = $this->configurableOptionsProvider->getProducts($product);

        foreach ($productVariants as $variant) {
            $variantStock = $this->getProductStockInfo($variant->getId());
            if ($variant->getStatus() != ProductStatus::STATUS_ENABLED || !$variantStock->getIsInStock()) {
                continue;
            }
            $variantAmount = $variant->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getAmount();

            if (!$minimumAmount
                || ($variantAmount->getValue() < $minimumAmount->getValue())
            ) {
                $minimumAmount = $variantAmount;
                $variantId = $variant->getId();
            }
        }
        if (!$variantId) {
            $variantId = $productVariants[0]->getId();
        }
        return $variantId;
    }

    /**
     * Get Parent Product ID By Child ID
     *
     * @param int $childId
     * @return mixed|string
     */
    private function getParentProductId(int $childId)
    {
        $product = $this->configurable->getParentIdsByChild($childId);
        if (isset($product[0])) {
            return $product[0];
        }
        return "";
    }

    /**
     * Get No of Products In Carousel.
     *
     * @return mixed
     */
    public function getNoOfProductsToShowInCarousel(): mixed
    {
        return $this->eavHelper->getConfigValue(self::NO_OF_PRODUCTS_TO_SHOW_IN_CAROUSEL);
    }

    /**
     * Get Recommended Products By ID.
     *
     * @param int $productId
     * @param string $type
     * @param int|null $pincode
     * @return array
     */
    public function getRecommendedProductsByID(int $productId, string $type, int $pincode = null): array
    {
        $recommendedProducts = [];
        try {
            $product = $this->productRepository->getById($productId);
            if ($product) {
                $productIds = match ($type) {
                    'up_sell' => $product->getUpSellProductIds(),
                    'cross_sell' => $product->getCrossSellProductIds(),
                    'related' => $product->getRelatedProductIds(),
                    default => [],
                };
                foreach ($productIds as $productId) {
                    $recommendedProduct = $this->formatProductForCarousel($productId, $pincode);
                    if ($recommendedProduct['stock_info']['is_in_stock']) {
                        $recommendedProducts[] = $recommendedProduct;
                    }
                }
                return $recommendedProducts;
            }
        } catch (NoSuchEntityException $exception) {
            $this->logger->error("Error | Fetch Recommended Product By ID | Product ID: " . $productId
                . " | Type: " . $type . " | " . __METHOD__ . $exception->getMessage());
        }
        return [];
    }

    /**
     * Format Catalog API response object
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatProductForPDP(int $productId, int $pincode = null): array
    {
        $product = $this->productRepository->getById($productId);
        $categories = [];
        foreach ($product->getCategoryIds() as $categoryId) {
            $categories[] = $this->categoryRepository->get($categoryId)->getName();
        }
        $commonProductData = $this->getProductAttributes($product, $pincode);
        $productData = [
            'media_gallery' => $this->getMediaGallery($product),
            'created_at' => $product->getCreatedAt(),
            'updated_at' => $product->getUpdatedAt(),
            'description' => $product->getCustomAttribute('description')
                ? $product->getCustomAttribute('description')->getValue()
                : "",
            'short_description' => $product->getCustomAttribute('short_description')
                ? $product->getCustomAttribute('short_description')->getValue()
                : "",
            'how_to_use' => $product->getCustomAttribute('how_to_use')
                ? $product->getCustomAttribute('how_to_use')->getValue()
                : "",
            'ingredients' => $product->getCustomAttribute('ingredients')
                ? $product->getCustomAttribute('ingredients')->getValue()
                : "",
            'product_information' => $this->eavHelper->getLabelValueFormat(
                $product,
                self::PRODUCT_INFORMATION_CONFIG_PATH
            ),
            'additional_information' => $this->eavHelper->getLabelValueFormat(
                $product,
                self::ADDITIONAL_INFORMATION_CONFIG_PATH
            ),
            'seo' => [
                'meta_title' => $product->getCustomAttribute('meta_title')
                    ? $product->getCustomAttribute('meta_title')->getValue()
                    : "",
                'meta_keywords' => $product->getCustomAttribute('meta_keywords')
                    ? $product->getCustomAttribute('meta_keywords')->getValue()
                    : "",
                'meta_description' => $product->getCustomAttribute('meta_description')
                    ? $product->getCustomAttribute('meta_description')->getValue()
                    : ""
            ],
            'usp' => $product->getCustomAttribute('usp')
                ? explode(',', $product->getCustomAttribute('usp')->getValue())
                : "",
            'primary_benefits' => $product->getCustomAttribute('primary_benefits')
                ? $this->eavHelper->getOptionLabel(
                    'primary_benefits',
                    $product->getCustomAttribute('primary_benefits')->getValue()
                ) : "",
            'manufacturer' => $product->getCustomAttribute('manufacturer')
                ? $product->getCustomAttribute('manufacturer')->getValue()
                : "",
            'qty_sold' => $product->getCustomAttribute('qty_sold')
                ? $product->getCustomAttribute('qty_sold')->getValue()
                : "",
            'customer_viewed' => $product->getCustomAttribute('customer_viewed')
                ? $product->getCustomAttribute('customer_viewed')->getValue()
                : "",
            'best_sale' => $this->getBestSale($product),
            'customer_bought' => $product->getCustomAttribute('customer_bought')
                ? $product->getCustomAttribute('customer_bought')->getValue()
                : "",
            'wallet_cashback_percent' => $product->getCustomAttribute('wallet_cashback_percent')
                ? $product->getCustomAttribute('wallet_cashback_percent')->getValue()
                : "",
            'is_cod_restricted' => $product->getCustomAttribute('is_cod_restricted')
                ? $product->getCustomAttribute('is_cod_restricted')->getValue()
                : "",
            'is_returnable' => $product->getCustomAttribute('is_returnable')
                ? $product->getCustomAttribute('is_returnable')->getValue()
                : ""
        ];

        $productData = array_merge($commonProductData, $productData);

        if (count($categories)) {
            $productData['category_ids'] = $product->getCategoryIds();
            $productData['category_names'] = $categories;
            $productData['category_tree'] = $this->getProductCategoryPath($product->getCategoryIds());
        }

        if ($product->getTypeId() == 'configurable') {
            $productData['configurable_product_links'] = $this->getChildProductIds($product);
            $productData['configurable_product_options'] = $this->getConfigurableProductOptions($product);

            $productData = $this->mergeLinkedConfigurableProducts($product, $productData);

            $productData['price'] = $productData['configurable_product_options'][0]['values']['minimum_price'];
            if (isset($productData['configurable_product_options'][0]['values']['minimum_special_price'])) {
                $productData['special_price'] = $productData['configurable_product_options'][0]
                ['values']['minimum_special_price'];
            }
            $productData['default_variant_id'] = $this->getDefaultVariantId($product->getId());
            if (isset($productData['configurable_product_options'][0]['values']['parent_hl_verified'])) {
                $productData['is_hl_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hl_verified'];
            }
            if (isset($productData['configurable_product_options'][0]['values']['parent_hm_verified'])) {
                $productData['is_hm_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hm_verified'];
            }
        }

        if ($product->getCustomAttribute('brand')) {
            $brand = $this->eavHelper->getOptionLabel(
                'brand',
                $product->getCustomAttribute('brand')->getValue()
            );
            $productData['brand'] = $this->getBrandData($brand);
        }

        if ($product->getCustomAttribute('categorization')) {
            $productData['categorization'] = $this->eavHelper->getOptionLabel(
                'categorization',
                $product->getCustomAttribute('categorization')->getValue()
            );
        } else {
            $productData['categorization'] = "";
        }

        if ($product->getCustomAttribute('faq_content')) {
            $faqContent = $product->getCustomAttribute('faq_content')->getValue() ?? "";
            $productData['faq_content'] = json_decode($faqContent, true);
        }

        $parentProductId = $this->getParentProductId($productId);

        if ($parentProductId) {
            $productData['parent_slug'] = $this->productRepository->getById($parentProductId)->getUrlKey();
        }

        $productData['content'] = $this->eavHelper->getLabelValueFormat(
            $product,
            self::CONTENT_ATTRIBUTE_CONFIG_PATH
        );

        $productData['custom_attributes'] = $this->eavHelper->getLabelValueFormat(
            $product,
            self::CUSTOM_ATTRIBUTE_CONFIG_PATH
        );

        return $productData;
    }

    /**
     * Merge linked configurable product children and options
     *
     * @param ProductInterface $product
     * @param array $productData
     * @return array
     */
    private function mergeLinkedConfigurableProducts(ProductInterface $product, array $productData): array
    {
        $linkedProductIds = $this->linkedProductResource->getLinkedProducts((int)$product->getId());

        if (!empty($linkedProductIds)) {
            foreach ($linkedProductIds as $linkedProductId) {
                try {
                    $linkedProduct = $this->productRepository->getById($linkedProductId);

                    $linkedChildIds = $this->getChildProductIds($linkedProduct);
                    if (!empty($linkedChildIds)) {
                        $productData['configurable_product_links'] = array_merge(
                            $productData['configurable_product_links'] ?? [],
                            $linkedChildIds
                        );
                    }

                    $linkedOptions = $this->getConfigurableProductOptions($linkedProduct);
                    if (!empty($linkedOptions)) {
                        $productData['configurable_product_options'] = array_merge(
                            $productData['configurable_product_options'] ?? [],
                            $linkedOptions
                        );
                    }
                } catch (NoSuchEntityException $exception) {
                    $this->logger->error("Error | Fetching linked product | Product ID: " . $productId
                        . " | Type: " . $type . " | " . __METHOD__ . $exception->getMessage());
                }
            }
        }

        if (!empty($productData['configurable_product_links'])) {
            $productData['configurable_product_links'] = array_values(
                    array_unique($productData['configurable_product_links'])
                );
        }

        if (!empty($productData['configurable_product_options'])) {
            $groupedOptions = [];

            foreach ($productData['configurable_product_options'] as $option) {
                $attributeCode = $option['attribute_code'] ?? null;

                if ($attributeCode) {
                    if (!isset($groupedOptions[$attributeCode])) {
                        $groupedOptions[$attributeCode] = $option;
                    } else {
                        if (isset($option['values']['variants'])) {
                            $groupedOptions[$attributeCode]['values']['variants'] = array_merge(
                                $groupedOptions[$attributeCode]['values']['variants'] ?? [],
                                $option['values']['variants']
                            );
                        }
                    }
                }
            }

            foreach ($groupedOptions as &$groupedOption) {
                if (isset($groupedOption['values']['variants'])) {
                    $groupedOption['values']['variants'] = array_map(
                        'unserialize',
                        array_unique(array_map('serialize', $groupedOption['values']['variants']))
                    );
                }
            }

            $productData['configurable_product_options'] = array_values($groupedOptions);
        }

        return $productData;
    }

    /**
     * Get Product Best sale this week
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getBestSale(ProductInterface $product): array
    {
        $bestSale = [];
        if ($product->getCustomAttribute('best_sale')) {
            $bestSaleSku = $product->getCustomAttribute('best_sale')->getValue();
            if (!$bestSaleSku) {
                return [];
            }
            $bestSaleSkuArray = array_unique(explode(",", $bestSaleSku));
            foreach ($bestSaleSkuArray as $productSku) {
                $productSku = trim($productSku);
                if ($productSku) {
                    try {
                        $bestSaleProduct = $this->productRepository->get($productSku);
                        $productStock = $this->getProductStockInfo($bestSaleProduct->getId());
                        if ($productStock->getIsInStock()) {
                            $productData = $this->getBestSaleProductData($bestSaleProduct);
                            $bestSale[] = $productData;
                        }
                    } catch (Exception $exception) {
                        continue;
                    }
                }
            }
        }
        return $bestSale;
    }

    /**
     * Get Best sale product data
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getBestSaleProductData(ProductInterface $product): array
    {
        $productData = [
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'status' => $product->getStatus(),
            'price' => $product->getPrice(),
            'type' => $product->getTypeId(),
            'slug' => $product->getCustomAttribute('url_key')->getValue(),
            'image' => $product->getImage(),
            'ratings' => $this->getAverageProductRating($product),
            'item_weight' => $product->getCustomAttribute('item_weight')
                ? $this->eavHelper->getOptionLabel(
                    'item_weight',
                    $product->getCustomAttribute('item_weight')->getValue()
                ) : "",
            'customer_viewed' => $product->getCustomAttribute('customer_viewed')
                ? $product->getCustomAttribute('customer_viewed')->getValue()
                : "",
        ];

        if ($product->getCustomAttribute('special_price')) {
            $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
            $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                ? $this->helper->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_from_date')->getValue()
                )
                : "";
            $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                ? $this->helper->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_to_date')->getValue()
                )
                : "";
        }

        if ($product->getCustomAttribute('brand')) {
            $brand = $this->eavHelper->getOptionLabel(
                'brand',
                $product->getCustomAttribute('brand')->getValue()
            );
            $productData['brand'] = $this->getBrandData($brand);
        }

        if ($product->getTypeId() == 'configurable') {
            $productData['configurable_product_links'] = $this->getChildProductIds($product);
            $productData['configurable_product_options'] = $this->getConfigurableProductOptions($product);
            $productData['price'] = $productData['configurable_product_options'][0]['values']['minimum_price'];
            if (isset($productData['configurable_product_options'][0]['values']['minimum_special_price'])) {
                $productData['special_price'] = $productData['configurable_product_options'][0]
                ['values']['minimum_special_price'];
            }
            $productData['default_variant_id'] = $this->getDefaultVariantId($product->getId());
            if (isset($productData['configurable_product_options'][0]['values']['parent_hl_verified'])) {
                $productData['is_hl_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hl_verified'];
            }
            if (isset($productData['configurable_product_options'][0]['values']['parent_hm_verified'])) {
                $productData['is_hm_verified'] = $productData['configurable_product_options']
                [0]['values']['parent_hm_verified'];
            }
        }

        return $productData;
    }

    /**
     * Get Brand Data
     *
     * @param string $brand
     * @return array
     * @throws NoSuchEntityException
     */
    private function getBrandData(string $brand): array
    {
        try {
            $brandSlug = "";
            $collection = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('name', $brand)->setPageSize(1);

            if ($collection->getSize()) {
                $categoryId = $collection->getFirstItem()->getId();
                $brandSlug = $this->categoryRepository->get($categoryId)->getUrlKey();
            }

            return [
                "label" => $brand,
                "value" => $brandSlug
            ];
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Category Assignment for products in form of tree.
     *
     * @param array $categoryIds
     * @return array|null
     */
    public function getProductCategoryPath(array $categoryIds): ?array
    {
        try {
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                $mainCategoryParentId = $this->getCategoryIdByUrl('categories');
                if ($key = array_keys($category->getPathIds(), $mainCategoryParentId)) {
                    $categoryName = "";
                    $pathIds = array_slice($category->getPathIds(), (int)$key[0] + 1);
                    $categoryCollection = $this->categoryCollectionFactory->create()
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('entity_id')
                        ->addAttributeToFilter('entity_id', ['in' => $pathIds]);
                    foreach ($categoryCollection as $categoryItem) {
                        $categoryName .= $categoryItem->getName() . '/';
                    }
                    if ($categoryName) {
                        $this->categoryPath[$category->getId()] = rtrim($categoryName, "/");
                    }
                }
            }
            return $this->categoryPath;
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
            return null;
        }
    }

    /**
     * Get Category ID By URL
     *
     * @param string $urlKey
     * @return int
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getCategoryIdByUrl(string $urlKey): int
    {
        $category = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('url_key', $urlKey)->getData();

        if (empty($category)) {
            throw new NoSuchEntityException(__('The category that was requested does not exists'));
        }

        return $category[0]['entity_id'];
    }

    /**
     * Get Product Offers By Slug
     *
     * @param string $slug
     * @param string|null $platform
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductOffersBySlug(string $slug, string $platform = null): array
    {
        $productId = $this->getProductIdByUrl($slug);
        $product = $this->productRepository->getById($productId);
        $offerRuleIds = $product->getCustomAttribute('bonus_offer_rule_id')
            ? $product->getCustomAttribute('bonus_offer_rule_id')->getValue()
            : '';

        $offerRuleIds = trim($offerRuleIds ?? '');
        $offerRuleIds = str_contains($offerRuleIds, '|')
            ? explode('|', $offerRuleIds)
            : explode(',', $offerRuleIds);

        $heroCouponRuleId = $product->getCustomAttribute('hero_coupon_rule_id')
            ? $product->getCustomAttribute('hero_coupon_rule_id')->getValue()
            : '';
        $heroCouponRuleId = trim($heroCouponRuleId ?? '');
        $heroCouponOfferData = [];

        $offersData = [];
        if (!empty($offerRuleIds)) {
            $salesRuleCollection = $this->salesRuleCollectionFactory->create()
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->addFieldToFilter('rule_id', ['in' => $offerRuleIds])
                ->addFieldToFilter('use_auto_generation', ['eq' => 0])
                ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
                ->addFieldToFilter(
                    'to_date',
                    [
                        ['gteq' => $this->getCurrentDate()],
                        ['null' => true]
                    ]
                )->addFieldToFilter(
                    'from_date',
                    [
                        ['lteq' => $this->getCurrentDate()],
                        ['null' => true]
                    ]
                )->setOrder('sort_order', 'asc');
            if ($salesRuleCollection->getSize()) {
                foreach ($salesRuleCollection as $salesRule) {
                    $excludedSkus = explode(',', $salesRule->getExcludeFromPdp() ?? '');
                    $excludedSkus = array_map('trim', $excludedSkus);
                    if (!in_array($product->getSku(), $excludedSkus)
                        && $salesRule->getId() != $heroCouponRuleId
                    ) {
                        list($savings, $savingsPercent) = $this->getCouponSavingsInfo($product, $salesRule);
                        $offersData[] = [
                            'rule_name' => $salesRule->getName(),
                            'coupon' => $salesRule->getCode(),
                            'rule_description' => $salesRule->getDescription(),
                            'term_and_conditions' => $salesRule->getTermAndConditions(),
                            'expiry' => $salesRule->getToDate(),
                            'savings' => $savings,
                            'savings_percent' => $savingsPercent,
                            'is_hero_coupon' => false,
                            'rule_type' => $salesRule->getRuleType()
                                ? $this->getRuleTypeLabel($salesRule->getRuleType())
                                : ''
                        ];
                    }
                }
            }
        }

        $salesRuleCollection = $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('show_on_pdp', ['eq' => 1])
            ->addFieldToFilter('rule_id', ['nin' => $offerRuleIds])
            ->addFieldToFilter('use_auto_generation', ['eq' => 0])
            ->addFieldToFilter('platform_used', ['in' => [$this->getPlatformValue($platform), 3]])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->addFieldToFilter(
                'from_date',
                [
                    ['lteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->setOrder('sort_order', 'asc');
        if ($salesRuleCollection->getSize()) {
            foreach ($salesRuleCollection as $salesRule) {
                $excludedSkus = explode(',', $salesRule->getExcludeFromPdp() ?? '');
                $excludedSkus = array_map('trim', $excludedSkus);
                if (!in_array($product->getSku(), $excludedSkus)
                    && $salesRule->getId() != $heroCouponRuleId
                ) {
                    list($savings, $savingsPercent) = $this->getCouponSavingsInfo($product, $salesRule);
                    $offersData[] = [
                        'rule_name' => $salesRule->getName(),
                        'coupon' => $salesRule->getCode(),
                        'rule_description' => $salesRule->getDescription(),
                        'term_and_conditions' => $salesRule->getTermAndConditions(),
                        'expiry' => $salesRule->getToDate(),
                        'savings' => $savings,
                        'savings_percent' => $savingsPercent,
                        'is_hero_coupon' => false,
                        'rule_type' => $salesRule->getRuleType()
                            ? $this->getRuleTypeLabel($salesRule->getRuleType())
                            : ''
                    ];
                }
            }
        }

        if (!empty($heroCouponRuleId)) {
            $heroCouponOfferData = $this->getHeroCouponOfferData($product, $heroCouponRuleId);
        }

        return array_merge($heroCouponOfferData, $offersData);
    }

    /**
     * Get Product ID By Product Slug
     *
     * @param string $url
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getProductIdByUrl(string $url): ?int
    {
        $product = $this->productFactory->create()->loadByAttribute('url_key', $url);

        if ($product) {
            return $product->getId();
        } else {
            throw new NoSuchEntityException(__('The product that was requested does not exists'));
        }
    }

    /**
     * Get Platform
     *
     * @param string|null $platform
     * @return int|mixed
     */
    private function getPlatformValue(string $platform = null): mixed
    {
        if ($platform) {
            foreach ($this->platformUsed->toOptionArray() as $option) {
                if (!strcasecmp($option['label'], $platform)) {
                    return $option['value'];
                }
            }
        }
        return 0;
    }

    /**
     * Get Current Date
     *
     * @return string
     */
    public function getCurrentDate(): string
    {
        return $this->timezoneInterface->date()->format('Y-m-d');
    }

    /**
     * Get Coupon Savings Data
     *
     * @param ProductInterface $product
     * @param Rule $rule
     * @return array
     */
    public function getCouponSavingsInfo(ProductInterface $product, Rule $rule): array
    {
        $savings = $savingsPercent = 0;
        try {
            $itemPrice = ($product->getSpecialPrice() && $this->validateSpecialPrice($product))
                ? (float)$product->getSpecialPrice()
                : $product->getPrice();
            $offerPrice = $itemPrice;
            $qty = 1;
            $ruleAction = $rule->getSimpleAction();
            if ($ruleAction == Rule::TO_PERCENT_ACTION) {
                $rulePercent = max(0, 100 - $rule->getDiscountAmount());
                $_rulePct = $rulePercent / 100;
                $savings = ($qty * $itemPrice) * $_rulePct;
            } elseif ($ruleAction == Rule::BY_PERCENT_ACTION) {
                $rulePercent = min(100, $rule->getDiscountAmount());
                $_rulePct = $rulePercent / 100;
                $savings = ($qty * $itemPrice) * $_rulePct;
            } elseif ($ruleAction == Rule::TO_FIXED_ACTION) {
                $savings = $qty * ($itemPrice - $rule->getDiscountAmount());
            } elseif ($ruleAction == Rule::BY_FIXED_ACTION) {
                $savings = min(($itemPrice * $qty), $rule->getDiscountAmount() * $qty);
            } elseif ($ruleAction == Rule::CART_FIXED_ACTION) {
                $savings = min(($itemPrice * $qty), $rule->getDiscountAmount());
            }
            if ($itemPrice > 0 && $savings > 0 && $savings <= $itemPrice) {
                $savingsPercent = ($savings / $itemPrice) * 100;
                $offerPrice = $itemPrice - $savings;
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }
        return [
            (float)number_format($savings, 2, '.', ''),
            (float)number_format($savingsPercent, 2, '.', ''),
            (float)number_format($offerPrice, 2, '.', '')
        ];
    }

    /**
     * Get Validate Special Price
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function validateSpecialPrice(ProductInterface $product): bool
    {
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate() ?
            $this->helper->getDateTimeBasedOnTimezone($product->getSpecialFromDate())
            : null;
        $specialToDate = $product->getSpecialToDate() ?
            $this->helper->getDateTimeBasedOnTimezone($product->getSpecialToDate())
            : null;
        $today = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        if ($specialPrice) {
            if ((is_null($specialFromDate) && is_null($specialToDate)) ||
                ($today >= $specialFromDate && is_null($specialToDate)) ||
                ($today <= $specialToDate && is_null($specialFromDate)) ||
                ($today >= $specialFromDate && $today <= $specialToDate)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Rule Type Label
     *
     * @param int $ruleType
     * @return string
     */
    private function getRuleTypeLabel(int $ruleType): mixed
    {
        if ($ruleType) {
            foreach ($this->ruleType->toOptionArray() as $option) {
                if ($option['value'] == $ruleType) {
                    return $option['label'];
                }
            }
        }
        return "";
    }

    /**
     * Get Hero Coupon Offer Data
     *
     * @param ProductInterface $product
     * @param int $ruleId
     * @return array
     */
    public function getHeroCouponOfferData(ProductInterface $product, int $ruleId): array
    {
        $offersData = [];
        try {
            $salesRule = $this->ruleFactory->create()->load($ruleId);
            if (empty($salesRule->getId())) {
                return $offersData;
            }
            if ($salesRule->getIsActive() == 1 && $salesRule->getUseAutoGeneration() == 0
                && (empty($salesRule->getFromDate()) || $salesRule->getFromDate() <= $this->getCurrentDate())
                && (empty($salesRule->getToDate()) || $salesRule->getToDate() >= $this->getCurrentDate())
            ) {
                list($savings, $savingsPercent, $offerPrice) = $this->getCouponSavingsInfo($product, $salesRule);
                $offersData[] = [
                    'rule_name' => $salesRule->getName(),
                    'coupon' => $salesRule->getCouponCode() ?: $salesRule->getCode(),
                    'rule_description' => $salesRule->getDescription(),
                    'term_and_conditions' => $salesRule->getTermAndConditions(),
                    'expiry' => $salesRule->getToDate(),
                    'savings' => $savings,
                    'savings_percent' => $savingsPercent,
                    'offer_price' => $offerPrice,
                    'is_hero_coupon' => true,
                    'rule_type' => $salesRule->getRuleType()
                        ? $this->getRuleTypeLabel($salesRule->getRuleType())
                        : ''
                ];
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }
        return $offersData;
    }

    /**
     * Get Is Special Price Exist
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function getIsSpecialPriceExist(ProductInterface $product): bool
    {
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate() ?
            $this->helper->getDateTimeBasedOnTimezone($product->getSpecialFromDate())
            : null;
        $specialToDate = $product->getSpecialToDate() ?
            $this->helper->getDateTimeBasedOnTimezone($product->getSpecialToDate())
            : null;
        $today = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        if ($specialPrice) {
            if ((is_null($specialFromDate) && is_null($specialToDate)) ||
                ($today >= $specialFromDate && is_null($specialToDate)) ||
                ($today <= $specialToDate && is_null($specialFromDate)) ||
                ($today >= $specialFromDate && $today <= $specialToDate)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Category Name By Id
     *
     * @param int $categoryId
     * @return string
     * @throws LocalizedException
     */
    public function getCategoryNameById(int $categoryId): string
    {
        /** @var Category $category */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('entity_id', ['eq' => $categoryId])
            ->getFirstItem();

        return $category->getName();
    }

    /**
     * Get products based on category id
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoryProductsBySlug(string $slug, int $pincode = null): array
    {
        /** @var Category $category */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('url_key', ['eq' => $slug])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        }
        return $this->formatCategoryResponse($category, $pincode);
    }

    /**
     * Get Category Tree Structure
     *
     * @param string $categorySlug
     * @return CategoryTreeInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCategoryTree(string $categorySlug): CategoryTreeInterface
    {
        /** @var Category $category */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('url_key', ['eq' => $categorySlug])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        }
        return $this->categoryManagement->getTree($category->getId());
    }

    /**
     * Get Category to show in bubble at home page.
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCategoryBubbles(): array
    {
        $categoryBubbles = [];

        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('show_bubble', ['eq' => 1])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->addAttributeToFilter('include_in_menu', ['eq' => 1])
            ->addAttributeToSort('position')
            ->getItems();

        /** @var Category $categoryItem */
        foreach ($categories as $categoryItem) {
            $categoryBubbles[] = [
                'id' => $categoryItem->getId(),
                'name' => $categoryItem->getName(),
                'slug' => $categoryItem->getUrlKey(),
                'image' => $this->removeMediaFromUrl($categoryItem->getImageUrl()),
                'category_thumbnail' => $this->removeMediaFromUrl($categoryItem->getCategoryThumbnail())
            ];
        }

        return $categoryBubbles;
    }

    /**
     * Remove Media From Url
     *
     * @param string|null $url
     * @return string|null
     */
    public function removeMediaFromUrl(?string $url): ?string
    {
        if ($url && str_contains($url, '/media')) {
            $url = explode('/media', $url, 2)[1];
        }
        return $url;
    }

    /**
     * Get Menu Items
     *
     * @param string $categorySlug
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoryInfo(string $categorySlug): array
    {
        $result = $categoryInfo = $prepaidDiscountSlab = [];

        $categoryId = $this->getCategoryIdByUrl($categorySlug);

        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->setOrder('name', 'asc');

        if ($categorySlug == 'brands') {
            $brands = $this->getAllBrands($categoryId);
            $categoriesData = $categories->addAttributeToFilter('top_brands', ['eq' => 1])
                ->getItems();
            $result['brands'] = $brands;
            /** @var Category $categoriesData */
            foreach ($categoriesData as $categoryData) {
                $categoryInfo[] = [
                    'id' => $categoryData->getId(),
                    'name' => $categoryData->getName(),
                    'slug' => $categoryData->getUrlKey(),
                    'category_thumbnail' => $this->removeMediaFromUrl($categoryData->getCategoryThumbnail())
                ];
            }
            $result['top_brands'] = $categoryInfo;
        }

        $category = $this->categoryRepository->get($categoryId);

        $result['category_info'] = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getUrlKey(),
            'image' => $this->removeMediaFromUrl($category->getImageUrl()),
            'category_thumbnail' => $this->removeMediaFromUrl($category->getCategoryThumbnail()),
            'founder_description' => $category->getFounderDescription(),
            'description' => $category->getDescription(),
            'seo' => [
                'meta_title' => $category->getMetaTitle()
                    ? $category->getMetaTitle()
                    : "",
                'meta_keywords' => $category->getMetaKeywords()
                    ? $category->getMetaKeywords()
                    : "",
                'meta_description' => $category->getMetaDescription()
                    ? $category->getMetaDescription()
                    : "",
                'seo_content' => $category->getSeoContent()
                    ? $category->getSeoContent()
                    : "",
            ]
        ];

        $prepaidDiscountRanges = $this->getConfig(self::PREPAID_DISCOUNT_SLAB);
        if ($prepaidDiscountRanges) {
            $items = json_decode($prepaidDiscountRanges, true);
            foreach ($items as $item) {
                $prepaidDiscountSlab[] = [
                    "from_price" => $item["from_price"],
                    "to_price" => $item["to_price"],
                    "discount_type" => $item["discount_type"],
                    "discount" => $item['discount']
                ];
            }
        }

        $result['config'] = [
            'minimum_order_value' => (int)$this->getConfig(self::MINIMUM_ORDER_VALUE),
            'delivery_charges' => (int)$this->getConfig(self::DELIVERY_CHARGES),
            'cod_min_order_total' => (int)$this->getConfig(self::COD_MIN_ORDER_TOTAL),
            'cod_max_order_total' => (int)$this->getConfig(self::COD_MAX_ORDER_TOTAL),
            'wallet' => [
                'threshold' => (int)$this->getConfig(self::STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH),
                'title' => $this->getConfig(self::STORE_CREDIT_TITLE),
                'conversion_rate' => $this->getConfig(self::CONVERSION_RATE_CONFIG_PATH),
                'default_toggle_behaviour' => $this->getConfig(self::DEFAULT_TOGGLE_BEHAVIOUR)
            ],
            'additional_label' => $this->getConfig(self::ADDITIONAL_LABEL_CONFIG_PATH),
        ];

        $result['is_free_shipping'] = $this->customDeliveryHelper->getIsFreeDelivery();
        $result['cod_limit'] = (int)$this->getConfig(self::COD_MAX_ORDER_TOTAL);
        $result['prepaid_discount'] = $prepaidDiscountSlab;

        return $result;
    }

    /**
     * Get Brands Menu Item
     *
     * @param int $categoryId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getAllBrands(int $categoryId): array
    {
        $result = [];
        $category = $this->categoryRepository->get($categoryId);
        $childrenCategories = $this->getChildrenCategories($category);
        foreach ($childrenCategories as $childrenCategory) {
            $categoryName = $childrenCategory->getName();
            $upperCaseCharacter = strtoupper($categoryName[0]);
            $result[$upperCaseCharacter . '/' . strtolower($upperCaseCharacter)][] = [
                "id" => $childrenCategory->getId(),
                "name" => $childrenCategory->getName(),
                "slug" => $childrenCategory->getUrlKey(),
                "category_thumbnail" => $this->removeMediaFromUrl($childrenCategory->getCategoryThumbnail())
            ];
        }
        ksort($result);
        return $result;
    }

    /**
     * Get Children Categories.
     *
     * @param Category $category
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|AbstractDb|AbstractCollection|null
     * @throws LocalizedException
     */
    private function getChildrenCategories(Category $category)
    {
        $collection = $category->getCollection();
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection->addAttributeToSelect(
            'url_key'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'all_children'
        )->addAttributeToSelect(
            'is_anchor'
        )->addAttributeToSelect(
            'category_thumbnail'
        )->addAttributeToFilter(
            'is_active',
            1
        )->addIdFilter(
            $category->getChildren()
        )->setOrder(
            'name',
            Select::SQL_ASC
        )->joinUrlRewrite();

        return $collection;
    }

    /**
     * Get Sub Categories By ID
     *
     * @param int $categoryId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSubCategoriesById(int $categoryId): array
    {
        $result = [];

        $category = $this->categoryRepository->get($categoryId);

        $subCategories = $category->getChildrenCategories()
            ->addAttributeToFilter('include_in_menu', ['eq' => 1]);
        foreach ($subCategories as $subCategory) {
            $result[] = [
                'id' => $subCategory->getId(),
                'name' => $subCategory->getName(),
                'slug' => $subCategory->getUrlKey(),
                'count' => $subCategory->getProductCollection()->getSize()
            ];
        }

        return $result;
    }

    /**
     * Get Shop By Category For PLP.
     *
     * @param string $categorySlug
     * @return array
     */
    public function getShopByCategoryForPLP(string $categorySlug): array
    {
        $result = [];
        try {
            $categoryId = $this->getCategoryIdByUrl($categorySlug);
            $subCategoriesIds = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('parent_id', ['eq' => $categoryId])
                ->addAttributeToFilter('show_in_shop_by', ['eq' => 1])
                ->setOrder('sequence', 'ASC')
                ->getItems();
            foreach ($subCategoriesIds as $subCategoryId => $subCategoryValue) {
                $subCategory = $this->categoryRepository->get($subCategoryId);
                $result[] = [
                    "id" => $subCategory->getId(),
                    "name" => $subCategory->getName(),
                    "slug" => $subCategory->getUrlKey(),
                    "desktop_image" => $this->removeMediaFromUrl($subCategory->getShopByImageDesktop()),
                    "mobile_image" => $this->removeMediaFromUrl($subCategory->getShopByImageMobile()),
                    "sequence" => (int)$subCategory->getSequence()
                ];
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Top Brand Deals For PLP
     *
     * @param string $categorySlug
     * @return array
     */
    public function getTopBrandDealsForPLP(string $categorySlug): array
    {
        $result = [];
        try {
            $categoryId = $this->getCategoryIdByUrl($categorySlug);
            $category = $this->categoryRepository->get($categoryId);
            $productIds = $category->getProductCollection()->getAllIds();
            $brandsName = $this->getUniqueAttributeValuesByProductIds('brand', $productIds);
            $result = $this->getTopBrandDealsBrandByBrandName(array_values($brandsName));
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Unique Attribute Values By Product Ids.
     *
     * @param string $attributeCode
     * @param array $productIds
     * @return array
     */
    public function getUniqueAttributeValuesByProductIds(string $attributeCode, array $productIds): array
    {
        $brands = [];
        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->getById($productId);
                if ($product->getCustomAttribute($attributeCode)) {
                    $brands[] = $this->eavHelper->getOptionLabel(
                        $attributeCode,
                        $product->getCustomAttribute($attributeCode)->getValue()
                    );
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e->getMessage() . __METHOD__);
            }
        }
        return array_unique($brands);
    }

    /**
     * Get "Top Brand Deals" Brand By Brand Name.
     *
     * @param array $brands
     * @return array
     */
    public function getTopBrandDealsBrandByBrandName(array $brands): array
    {
        $result = [];
        try {
            $collection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect("*")
                ->addAttributeToFilter('name', ['in' => $brands])
                ->addAttributeToFilter('show_in_shop_by', ['eq' => 1])
                ->setOrder('sequence', 'ASC');

            foreach ($collection as $brandCategory) {
                $result[] = [
                    "id" => $brandCategory->getId(),
                    "label" => $brandCategory->getName(),
                    "slug" => $brandCategory->getUrlKey(),
                    "desktop_image" => $this->removeMediaFromUrl($brandCategory->getShopByImageDesktop()),
                    'mobile_image' => $this->removeMediaFromUrl($brandCategory->getShopByImageMobile()),
                    "sequence" => $brandCategory->getSequence()
                ];
            }
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Return Category Details based on input provided.
     *
     * @param string $searchTerm
     * @return array
     * @throws LocalizedException
     */
    public function searchCategories(string $searchTerm): array
    {
        $result = [];
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->addAttributeToFilter('include_in_search', ['eq' => 1])
            ->addAttributeToFilter('name', ['like' => '%' . $searchTerm . '%']);
        foreach ($categories as $category) {
            $result[] = [
                "id" => $category->getId(),
                "name" => $category->getName(),
                "url_key" => $category->getUrlKey()
            ];
        }
        return $result;
    }

    /**
     * Return Categories List with type (L1 or L2)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCategoriesList(): array
    {
        $result = [];
        $rootCategory = $this->categoryRepository->get(self::ROOT_CATEGORY_ID);
        $l1Categories = $rootCategory->getChildrenCategories();

        foreach ($l1Categories as $l1Category) {
            if (!$l1Category->getIsActive()) {
                continue;
            }

            // Add L1 category
            $result[$l1Category->getUrlKey()] = [
                'id' => $l1Category->getId(),
                'type' => 'l1',
                'name' => $l1Category->getName()
            ];

            // Add L2 categories
            $l2Categories = $l1Category->getChildrenCategories();
            foreach ($l2Categories as $l2Category) {
                if ($l2Category->getIsActive()) {
                    $result[$l2Category->getUrlKey()] = [
                        'id' => $l2Category->getId(),
                        'type' => 'l2',
                        'name' => $l2Category->getName()
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * Get Product Info By Slug For Data Analytics
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductInfoBySlug(int $productId): array
    {
        $product = $this->productRepository->getById($productId);
        return $this->formatProductForAnalytics($product);
    }

    /**
     * Format Product Response For Data Analytics
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatProductForAnalytics(ProductInterface $product): array
    {
        $productStock = $this->getProductStockInfo($product->getId());

        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'slug' => $product->getCustomAttribute('url_key')->getValue(),
            'stock_status' => $productStock->getIsInStock(),
            'price' => $product->getPrice(),
            'special_price' => ($product->getSpecialPrice() && $this->validateSpecialPrice($product)) ?
                (float)$product->getSpecialPrice()
                : null,
            'image' => $product->getImage(),
            'stock_info' => [
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
            ],
        ];

        if ($product->getCustomAttribute('primary_l1_category')) {
            $productData['primary_l1_category'] = $this->getCategoryNameAndSlugById(
                $product->getCustomAttribute('primary_l1_category')->getValue()
            );
        }

        if ($product->getCustomAttribute('primary_l2_category')) {
            $productData['primary_l2_category'] = $this->getCategoryNameAndSlugById(
                $product->getCustomAttribute('primary_l2_category')->getValue()
            );
        }

        if ($product->getCustomAttribute('primary_benefits')) {
            $productData['primary_benefits'] = $this->eavHelper->getOptionLabel(
                'primary_benefits',
                $product->getCustomAttribute('primary_benefits')->getValue()
            );
        }

        if ($product->getCustomAttribute('brand')) {
            $brand = $this->eavHelper->getOptionLabel(
                'brand',
                $product->getCustomAttribute('brand')->getValue()
            );
            $productData['brand'] = $this->getBrandData($brand);
        }

        return $productData;
    }

    /**
     * Get Product Info By ID For Data Analytics
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductInfoById(int $productId): array
    {
        $product = $this->productRepository->getById($productId);
        return $this->formatProductForAnalytics($product);
    }

    /**
     * Get Product Info By SKU For Data Analytics
     *
     * @param string $sku
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductInfoBySku(string $sku): array
    {
        $product = $this->productRepository->get($sku);
        return $this->formatProductForAnalytics($product);
    }

    /**
     * Attributes Mapping API
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAttributesMapping(): array
    {
        $result = [];
        $mappingData = $this->attributeMappingFactory->create()->getCollection();
        foreach ($mappingData as $mapping) {
            $result[] = [
                'category_id' => $mapping->getCategoryId(),
                'category_slug' => $mapping->getCategorySlug(),
                'attributes' => explode(',', $mapping->getAttributes()),
            ];
        }
        return $result;
    }

    /**
     * Get Customer Widgets Data
     *
     * @param int $customerId
     * @param int $productCount
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCustomerWidgets(int $customerId, int $productCount = 2): array
    {
        $categoryId = (int)$this->getConfig(self::BEST_DEALS_CATEGORY_ID);

        $deliveredOrderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addAttributeToSort('created_at', 'desc')
            ->addFieldToFilter('status', 'delivered');


        $buyAgainProducts = [];
        $bestDealsProducts = [];
        $orderReviewProducts = [];
        $productIds = [
            'buy_again' => [],
            'best_deals' => [],
            'order_review' => []
        ];
        $formattedProducts = [];

        foreach ($deliveredOrderCollection as $order) {
            $orderItems = $order->getAllVisibleItems();

            foreach ($orderItems as $item) {
                $productId = $item->getProductId();

                try {
                    $stockItem = $this->getProductStockInfo($productId);
                    if (!$stockItem || !$stockItem->getIsInStock()) {
                        continue;
                    }

                    $product = $this->productRepository->getById($productId);

                    if (!isset($formattedProducts[$productId])) {
                        $formattedProduct = $this->formatProductForCarousel($productId);
                        $formattedProducts[$productId] = $formattedProduct;
                    } else {
                        $formattedProduct = $formattedProducts[$productId];
                    }

                    if (count($buyAgainProducts) < $productCount &&
                        !in_array($productId, $productIds['buy_again'])
                    ) {
                        $buyAgainProducts[] = $formattedProduct;
                        $productIds['buy_again'][] = $productId;
                    }

                    if (in_array($categoryId, $product->getCategoryIds()) &&
                        count($bestDealsProducts) < $productCount &&
                        !in_array($productId, $productIds['best_deals'])
                    ) {
                        $bestDealsProducts[] = $formattedProduct;
                        $productIds['best_deals'][] = $productId;
                    }

                    if (!in_array($productId, $productIds['order_review']) &&
                        $this->isProductInUnratedShipment($order->getId(), $productId) &&
                        count($orderReviewProducts) < $productCount
                    ) {
                        $orderReviewProducts[] = $formattedProduct;
                        $productIds['order_review'][] = $productId;
                    }
                } catch (LocalizedException $exception) {
                    continue;
                }
            }
        }

        $buyAgainProducts = array_slice($buyAgainProducts, 0, $productCount);
        $bestDealsProducts = array_slice($bestDealsProducts, 0, $productCount);

        return [
            'buy_again' => [
                'label' => 'Buy Again',
                'products' => $buyAgainProducts
            ],
            'recently_viewed' => [
                'label' => 'Recently Viewed',
                'products' => []
            ],
            'order_review' => [
                'label' => 'Leave us a review!',
                'products' => $orderReviewProducts
            ],
            'best_deals' => [
                'label' => 'Best Deals for You',
                'products' => $bestDealsProducts
            ]
        ];
    }

    /**
     * Check if product exists in any unrated shipment for an order
     *
     * @param int $orderId
     * @param int $productId
     * @return bool
     */
    protected function isProductInUnratedShipment(int $orderId, int $productId): bool
    {
        $shipmentCollection = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('shipment_status', 4)
            ->addFieldToFilter('is_rated', ['null' => true]);
        foreach ($shipmentCollection as $shipment) {
            foreach ($shipment->getAllItems() as $item) {
                if ((int)$item->getProductId() === $productId) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Is Swatch Attribute
     *
     * @param string $attributeCode
     * @return bool
     */
    private function isSwatchAttribute(string $attributeCode): bool
    {
        try {
            $attribute = $this->eavConfig->getAttribute(self::ENTITY_TYPE, $attributeCode);
            return $this->swatchHelper->isSwatchAttribute($attribute);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return false;
    }
}
