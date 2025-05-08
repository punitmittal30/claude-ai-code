<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Helper;

use DateTime;
use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\Operation\Read\ReadExtensions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Helper\Data;
use Pratech\Catalog\Helper\Eav;
use Pratech\Catalog\Helper\Product as ProductHelper;
use Psr\Log\LoggerInterface;

/**
 * External Catalog Helper Class
 */
class ExternalCatalog
{
    /**
     * Load attribute codes once
     *
     * @var array
     */
    private $attributeCodes = [];

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ReadExtensions
     */
    private $readExtensions;

    /**
     * External Catalog Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StockRegistryInterface $stockItemRepository
     * @param Configurable $configurable
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param CollectionFactory $collectionFactory
     * @param Attribute $attribute
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param Data $baseHelper
     * @param Eav $eavHelper
     * @param ProductHelper $productHelper
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param ReadExtensions|null $readExtensions
     */
    public function __construct(
        private ProductRepositoryInterface                  $productRepository,
        private CategoryRepositoryInterface                 $categoryRepository,
        private ScopeConfigInterface                        $scopeConfig,
        private StockRegistryInterface                      $stockItemRepository,
        private Configurable                                $configurable,
        private TimezoneInterface                           $timezone,
        private LoggerInterface                             $logger,
        private ConfigurableOptionsProviderInterface        $configurableOptionsProvider,
        private \Magento\Framework\Stdlib\DateTime\DateTime $date,
        private CollectionFactory                           $collectionFactory,
        private Attribute                                   $attribute,
        private JoinProcessorInterface                      $extensionAttributesJoinProcessor,
        private ProductSearchResultsInterfaceFactory        $searchResultsFactory,
        private Data                                        $baseHelper,
        private Eav                                         $eavHelper,
        private ProductHelper                               $productHelper,
        CollectionProcessorInterface                        $collectionProcessor = null,
        ReadExtensions                                      $readExtensions = null
    ) {
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
        $this->readExtensions = $readExtensions ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(ReadExtensions::class);
    }

    /**
     * Get product list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    public function getProductList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection);

        $collection->addAttributeToSelect('*');
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $this->joinPositionField($collection, $searchCriteria);

        $this->collectionProcessor->process($searchCriteria, $collection);

        $collection->load();

        $collection->addCategoryIds();
        $this->addExtensionAttributes($collection);

        $products = array_map([$this, 'formatProduct'], $collection->getItems());

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($products);
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                // phpstan:ignore "Class Magento\Catalog\Model\Api\SearchCriteria\ProductCollectionProcessor not found."
                \Magento\Catalog\Model\Api\SearchCriteria\ProductCollectionProcessor::class
            );
        }
        return $this->collectionProcessor;
    }

    /**
     * Join category position field to make sorting by position possible.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @return void
     */
    private function joinPositionField(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria
    ): void {
        $categoryIds = [[]];
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'category_id') {
                    $filterValue = $filter->getValue();
                    $categoryIds[] = is_array($filterValue) ? $filterValue : explode(',', $filterValue ?? '');
                }
            }
        }
        $categoryIds = array_unique(array_merge(...$categoryIds));
        if (count($categoryIds) === 1) {
            $collection->joinField(
                'position',
                'catalog_category_product',
                'position',
                'product_id=entity_id',
                ['category_id' => current($categoryIds)],
                'left'
            );
        }
    }

    /**
     * Add extension attributes to loaded items.
     *
     * @param Collection $collection
     * @return Collection
     */
    private function addExtensionAttributes(Collection $collection) : Collection
    {
        foreach ($collection->getItems() as $item) {
            $this->readExtensions->execute($item);
        }
        return $collection;
    }

    /**
     * Format Product Response
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatProduct(ProductInterface $product): array
    {
        $productData = $this->getProductAttributes($product);

        if ($product->getTypeId() == 'configurable') {
            $productData['configurable_product_options'] = $this->getConfigurableProductOptions($product);
            $productData['price'] = $productData['configurable_product_options']['minimum_price'];
            if (isset($productData['configurable_product_options']['minimum_special_price'])) {
                $productData['special_price'] = $productData['configurable_product_options']['minimum_special_price'];
            }
            $productData['default_variant_id'] = $this->getDefaultVariantId($product);
        }

        $parentProductId = $this->getParentProductId($product->getId());
        if ($parentProductId) {
            $productData['parent_id'] = $parentProductId;
        }

        return $productData;
    }

    /**
     * Get Product Attributes
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductAttributes(ProductInterface $product): array
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
            'ratings' => $this->productHelper->getAverageProductRating($product),
            'description' => $product->getCustomAttribute('description')
                ? $product->getCustomAttribute('description')->getValue()
                : "",
            'visibility' => $product->getAttributeText('visibility'),
            'stock_info' => [
                'qty' => $productStock->getQty(),
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
                'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
            ],
            'media_gallery' => $this->getMediaGallery($product),
            'item_weight' => $product->getCustomAttribute('item_weight')
                ? $this->eavHelper->getOptionLabel(
                    'item_weight',
                    $product->getCustomAttribute('item_weight')->getValue()
                ) : "",
            'replenishment_time' => $product->getCustomAttribute('replenishment_time') ?
                    $product->getCustomAttribute('replenishment_time')->getValue()
                    : "",
            'length' => $product->getCustomAttribute('length') ?
                    $product->getCustomAttribute('length')->getValue()
                    : "",
            'width' => $product->getCustomAttribute('width') ?
                    $product->getCustomAttribute('width')->getValue()
                    : "",
            'height' => $product->getCustomAttribute('height') ?
                    $product->getCustomAttribute('height')->getValue()
                    : "",
            'color' => $product->getCustomAttribute('color')
                ? $this->eavHelper->getOptionLabel(
                    'color',
                    $product->getCustomAttribute('color')->getValue()
                ) : "",
            'dietary_preference' => $product->getCustomAttribute('dietary_preference')
                ? $this->eavHelper->getOptionLabel(
                    'dietary_preference',
                    $product->getCustomAttribute('dietary_preference')->getValue()
                ) : "",
            'form' => $product->getCustomAttribute('form')
                ? $this->eavHelper->getOptionLabel(
                    'form',
                    $product->getCustomAttribute('form')->getValue()
                ) : "",
            'ean_code' => $product->getCustomAttribute('ean_code')
                ? $this->eavHelper->getOptionLabel(
                    'ean_code',
                    $product->getCustomAttribute('ean_code')->getValue()
                ) : "",
            'wp_product_id' => $product->getCustomAttribute('wp_product_id')
                ? $this->eavHelper->getOptionLabel(
                    'wp_product_id',
                    $product->getCustomAttribute('wp_product_id')->getValue()
                ) : "",
            'brand' => $product->getCustomAttribute('brand') ?
                $this->baseHelper->getProductAttributeLabel(
                    'brand',
                    $product->getCustomAttribute('brand')->getValue()
                ) : "",
            'primary_l1_category' => $product->getCustomAttribute('primary_l1_category') ?
                $this->baseHelper->getCategoryData(
                    $product->getCustomAttribute('primary_l1_category')->getValue()
                ) : "",
            'primary_l2_category' => $product->getCustomAttribute('primary_l2_category') ?
                $this->baseHelper->getCategoryData(
                    $product->getCustomAttribute('primary_l2_category')->getValue()
                ) : "",
        ];

        // concern
        if ($product->getCustomAttribute('concern')) {
            $values = [];
            $attributeValues = explode(',', $product->getCustomAttribute('concern')->getValue());
            foreach ($attributeValues as $attributeValue) {
                $values[] = $this->eavHelper->getOptionLabel(
                    'concern',
                    $attributeValue
                );
            }
            $productData['concern'] = implode(', ', $values);
        }

        // special price
        if ($product->getCustomAttribute('special_price')) {
            $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
            $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_from_date')->getValue()
                )
                : "";
            $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_to_date')->getValue()
                )
                : "";
        }

        // coupon offer for hero_coupon_rule_id
        $heroCouponRuleId = $product->getCustomAttribute('hero_coupon_rule_id')
            ? $product->getCustomAttribute('hero_coupon_rule_id')->getValue()
            : '';
        $heroCouponRuleId = trim($heroCouponRuleId ?? '');
        $heroCouponOfferData = [];
        if (!empty($heroCouponRuleId)) {
            $productData['coupon_offers'] = $this->productHelper->getHeroCouponOfferData($product, $heroCouponRuleId);
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
     * Get Configurable Product Options
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfigurableProductOptions(ProductInterface $product): array
    {
        $configurableOptions = [];
        $variantSkus = [];

        /** @var OptionInterface[] $options */
        $options = $product->getExtensionAttributes()->getConfigurableProductOptions();

        foreach ($options as $option) {
            // Check if attribute code is already loaded
            if (!isset($this->attributeCodes[$option->getAttributeId()])) {
                $this->attributeCodes[$option->getAttributeId()] = $this->attribute->load(
                    $option->getAttributeId()
                )->getAttributeCode();
            }

            $optionValues = $this->getConfigurableOptionValues($option->getAttributeId(), $product);

            $configurableOptions[] = [
                'attribute_id' => $option->getAttributeId(),
                'label' => $option->getLabel(),
                'position' => $option->getPosition(),
                'product_id' => $option->getProductId(),
                'attribute_code' => $this->attributeCodes[$option->getAttributeId()],
                'values' => $optionValues,
            ];

            foreach ($optionValues as $optionValue) {
                $variantSkus[] = $optionValue['sku'];
            }
        }
        $configurableProductOptions['options'] = $configurableOptions;

        $variantSkus = array_unique($variantSkus);
        $configurableProductVariants = $this->getConfigurableProductVariants($variantSkus, $product);

        return array_merge($configurableProductOptions, $configurableProductVariants);
    }

    /**
     * Get Configurable Options Data.
     *
     * @param string|null $optionId
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableOptionValues(?string $optionId, ProductInterface $product): array
    {
        $optionValues = [];
        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $configurableOptions = $typeInstance->getConfigurableOptions($product);
        foreach ($configurableOptions as $key => $configurableOption) {
            if ($optionId == $key) {
                foreach ($configurableOption as $option) {
                    $optionValues[] = [
                        'sku' => $option['sku'],
                        'value_index' => $option['value_index'],
                        'label' => $option['option_title']
                    ];
                }
            }
        }
        return $optionValues;
    }

    /**
     * Get Configurable Product Variants Data.
     *
     * @param array $variantSkus
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductVariants(array $variantSkus, ProductInterface $product): array
    {
        $result = [];
        $minimumRegularPrice = $minimumSpecialPrice = 0;
        $variants = [];
        foreach ($variantSkus as $variantSku) {
            $simpleProduct = $this->productRepository->get($variantSku);
            if ($simpleProduct->getStatus() != ProductStatus::STATUS_ENABLED) {
                continue;
            }
            $productStock = $this->getProductStockInfo($simpleProduct->getId());
            $variant = $this->getProductAttributes($simpleProduct);
            $variant['product_id'] = $simpleProduct->getId();
            $variant['stock_status'] = $productStock->getIsInStock();
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
            $variants[] = $variant;
        }
        $result['minimum_price'] = $minimumRegularPrice;
        if ($minimumSpecialPrice != 0) {
            $result['minimum_special_price'] = $minimumSpecialPrice;
        }
        $result['variants'] = $variants;
        return $result;
    }

    /**
     * Get Default Variant ID for Configurable Products
     *
     * @param ProductInterface $product
     * @return int
     * @throws NoSuchEntityException
     */
    private function getDefaultVariantId(ProductInterface $product): int
    {
        $minimumAmount = null;
        $variantId = null;

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
    private function getParentProductId(int $childId): mixed
    {
        $product = $this->configurable->getParentIdsByChild($childId);
        if (isset($product[0])) {
            return $product[0];
        }
        return "";
    }
}
