<?php

namespace Hyuga\Catalog\Model\Repository;

use Exception;
use Hyuga\Catalog\Api\ProductRepositoryInterface;
use Hyuga\Catalog\Service\ProductAttributeService;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Helper\Data as Helper;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Helper\Eav;
use Pratech\Catalog\Model\CalculatePricePerAttributes;
use Pratech\ReviewRatings\Model\ProductRatings;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Product Entity Type
     */
    public const ENTITY_TYPE = 'catalog_product';
    public const CONFIGURABLE_ATTRIBUTE_CONFIG_PATH = 'product/attributes/configurable_attributes';
    public const ATTRIBUTE_SUFFIX_CONFIG_PATH = [
        'price_per_count' => 'product/attribute_suffix/price_per_count',
        'price_per_100_ml' => 'product/attribute_suffix/price_per_100_ml',
        'price_per_100_gram' => 'product/attribute_suffix/price_per_100_gram',
        'price_per_gram_protein' => 'product/attribute_suffix/price_per_gram_protein'
    ];
    public const ADDITIONAL_LABEL_CONFIG_PATH = 'product/general/additional_label';
    public array $pricePerAttributesList = [
        'price_per_count',
        'price_per_100_ml',
        'price_per_100_gram',
        'price_per_gram_protein'
    ];

    /**
     * Product Helper Constructor
     *
     * @param StockRegistryInterface $stockItemRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRatings $productRatings
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurable
     * @param Eav $eavHelper
     * @param Logger $logger
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param Helper $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param CalculatePricePerAttributes $calculatePricePerAttributes
     * @param Attribute $attribute
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param ProductAttributeService $productAttributeService
     */
    public function __construct(
        private StockRegistryInterface                          $stockItemRepository,
        private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface                     $categoryRepository,
        private ProductRatings                                  $productRatings,
        private StoreManagerInterface                           $storeManager,
        private Configurable                                    $configurable,
        private Eav                                             $eavHelper,
        private Logger                                          $logger,
        private ConfigurableOptionsProviderInterface            $configurableOptionsProvider,
        private Helper                                          $helper,
        private ScopeConfigInterface                            $scopeConfig,
        private CalculatePricePerAttributes                     $calculatePricePerAttributes,
        private Attribute                                       $attribute,
        private DeliveryDateCalculator                          $deliveryDateCalculator,
        private ProductAttributeService                         $productAttributeService,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProductById(int $productId, int $pincode = null, string $section = ''): array
    {
        $productData = [];

        $product = $this->productRepository->getById($productId);

        switch ($section) {
            case 'carousel':
                $this->loadCarouselProductData($product, $pincode);
                break;
            case 'plp':
                $this->loadCarouselProductData($product, $pincode);
                break;
            case 'pdp':
                $this->loadCarouselProductData($product, $pincode);
                break;
            case 'search':
                $this->loadCarouselProductData($product, $pincode);
                break;
            case 'related':
                $this->loadCarouselProductData($product, $pincode);
                break;
            case 'minicart':
                $this->loadCarouselProductData($product, $pincode);
                break;
            default:
                $this->loadCarouselProductData($product, $pincode);
        }

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

    public function loadCarouselProductData(ProductInterface $product, int $pincode = null)
    {
        $allAttributes = $this->productAttributeService->getAllAttributes($product->getId());
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
            ->debug('CUSTOM_LOGGING', ['$allAttributes' => $allAttributes]);
    }

    /**
     * Get Static Product Attributes
     *
     * @param ProductInterface $product
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function loadStableProductAttributes(ProductInterface $product, int $pincode = null): array
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
     * Append selected configurable attributes to product data response
     *
     * @param ProductInterface $product
     * @param array $productData
     * @return array
     */
    private function appendConfigurableAttributes(
        ProductInterface $product,
        array            $productData
    )
    {
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
        ?string          $optionId,
        ProductInterface $product,
        int              $pincode = null
    ): array
    {
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
                    $variant = array_merge($variant, $this->loadStableProductAttributes($simpleProduct, $pincode));
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
}
