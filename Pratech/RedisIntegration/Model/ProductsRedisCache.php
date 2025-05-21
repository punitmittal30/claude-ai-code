<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Model;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Predis\Client;

class ProductsRedisCache
{
    /**
     * Product Key Identifier
     */
    public const PRODUCT_ID_PREFIX = "catalog:product:id";
    public const PRODUCT_SLUG_PREFIX = "catalog:product:slug";

    /**
     * Category Key Identifier
     */
    public const CATEGORY_ID_PREFIX = "catalog:category:id";
    public const CATEGORY_SLUG_PREFIX = "catalog:category:slug";

    /**
     * Recommendation Key Identifier
     */
    public const CROSS_SELL_ID_PREFIX = "crosssell:product:id";
    public const CROSS_SELL_SLUG_PREFIX = "crosssell:product:slug";
    public const UPSELL_ID_PREFIX = "upsell:product:id";
    public const UPSELL_SLUG_PREFIX = "upsell:product:slug";
    public const RELATED_ID_PREFIX = "related:product:id";
    public const RELATED_SLUG_PREFIX = "related:product:slug";

    /**
     * Analytics Product Prefix Constant
     */
    public const ANALYTICS_PRODUCT_ID_PREFIX = "analytics:product:id";
    public const ANALYTICS_PRODUCT_SKU_PREFIX = "analytics:product:sku";
    public const ANALYTICS_PRODUCT_SLUG_PREFIX = "analytics:product:slug";

    /**
     * Product Review Key Identifier
     */
    public const REVIEWS_ID_PREFIX = "product:reviews:id";
    public const REVIEWS_SLUG_PREFIX = "product:reviews:slug";

    /**
     * Search Prefix Identifier for Old search
     */
    public const SEARCH_PREFIX_V1 = 'v1:search:term';
    public const SEARCH_TERM_PREFIX_V1 = 'v1:search:pad:results:query';

    /**
     * Search Prefix Identifier for New search
     */
    public const SEARCH_PREFIX_V2 = 'v2:search:term';
    public const SEARCH_TERM_PREFIX_V2 = 'v2:search:pad:results:query';

    /**
     * Menu API Prefix Identifier
     */
    public const MENU_API_PREFIX = 'catalog:category:slug:root';

    /**
     * PLP Prefix Identifier
     */
    public const PLP_PREFIX = 'plp:result';

    /**
     * Categories List with L1/L2 Type
     */
    public const CATEGORIES = "categories";

    /**
     * Category Mapping Prefix
     */
    public const CATEGORY_MAPPING_PREFIX = "catalog:categories:mapping";

    /**
     * Filters Position Identifier
     */
    public const FILTERS_POSITION_PREFIX = 'filters:position';

    /**
     * Product Attribute Mapping Prefix Constant
     */
    public const PRODUCT_ATTRIBUTE_MAPPING_PREFIX = "category:attributes";

    public const EXTERNAL_PLATFORM = ["dpanda"];

    /**
     * Banner Slug Key Identifier
     */
    public const BANNER_PREFIX = "banners";

    /**
     * Category Info Slug cache key
     */
    public const CATEGORY_INFO_SLUG = "category:info:slug";

    /**
     * SHOP BY CATEGORY Identifier
     */
    public const SHOP_BY_CATEGORY = 'shop-by-category';

    /**
     * Products Offer Slug Key Identifier
     */
    public const PRODUCTS_OFFER_SLUG_PREFIX = "catalog:product:slug:offers";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * @param RedisConnection $redisConnection
     * @param RedisCacheLogger $redisCacheLogger
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Configurable $configurable
     */
    public function __construct(
        RedisConnection                     $redisConnection,
        private RedisCacheLogger            $redisCacheLogger,
        private ProductRepositoryInterface  $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private Configurable                $configurable
    ) {
        $this->redisConnection = $redisConnection->connect();
    }

    /**
     * Delete Review Cache
     *
     * @param int $productId
     * @return void
     */
    public function deleteProductReview(int $productId): void
    {
        try {
            if ($this->redisConnection) {
                $product = $this->productRepository->getById($productId);

                $this->deleteProductReviewById($productId);
                $this->deleteProductReviewBySlug($product->getUrlKey());
                $this->deleteProduct($productId);
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("deleteReviewCache | Product ID: " . $productId .
                " cache clearing issue | " . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }

    /**
     * Delete Product Review By Id.
     *
     * @param int $productId
     * @return void
     */
    public function deleteProductReviewById(int $productId): void
    {
        if ($this->validateExistingKey(self::REVIEWS_ID_PREFIX . ":" . $productId)) {
            $this->redisConnection->del($this->getKeys(self::REVIEWS_ID_PREFIX . ":" . $productId));
        }
    }

    /**
     * Validate Existing Cache Key.
     *
     * @param string $keyIdentifier
     * @return bool
     */
    public function validateExistingKey(string $keyIdentifier): bool
    {
        return $this->redisConnection && count($this->redisConnection->keys($keyIdentifier));
    }

    /**
     * Get Already Existing Redis Keys.
     *
     * @param string $pattern
     * @return array
     */
    private function getKeys(string $pattern): array
    {
        return $this->redisConnection->keys($pattern);
    }

    /**
     * Delete Product Review By Slug.
     *
     * @param string $productSlug
     * @return void
     */
    public function deleteProductReviewBySlug(string $productSlug): void
    {
        if ($this->validateExistingKey(self::REVIEWS_SLUG_PREFIX . ":" . $productSlug)) {
            $this->redisConnection->del($this->getKeys(self::REVIEWS_SLUG_PREFIX . ":" . $productSlug));
        }
    }

    /**
     * Delete Product Cache.
     *
     * @param int $productId
     * @return void
     */
    public function deleteProduct(int $productId): void
    {
        try {
            if ($this->redisConnection) {

                $product = $this->productRepository->getById($productId);
                $productSlug = $product->getUrlKey();

                // Clear product cache
                $this->deleteProductById($productId);
                $this->deleteProductBySlug($productSlug);

                // Clear recommended products cache
                $this->deleteCrossSellById($productId);
                $this->deleteCrossSellBySlug($productSlug);
                $this->deleteUpSellById($productId);
                $this->deleteUpSellBySlug($productSlug);
                $this->deleteRelatedProductsById($productId);
                $this->deleteRelatedProductsBySlug($productSlug);

                $this->deleteAnalytics($product->getId(), $product->getSku(), $productSlug);

                foreach ($product->getCategoryIds() as $categoryId) {
                    $this->deleteCategory($categoryId);
                }

                $parentProductId = $this->getParentProductId($product->getId());

                if ($parentProductId) {
                    $parentProduct = $this->productRepository->getById($parentProductId);

                    // Clear child product cache
                    $this->deleteProductById($productId);
                    $this->deleteProductBySlug($productSlug);

                    foreach ($parentProduct->getCategoryIds() as $parentCategoryId) {
                        $this->deleteCategory($parentCategoryId);
                    }
                }
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("deleteProductCache | Product ID: " . $productId .
                " cache clearing issue | " . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }

    /**
     * Delete Product By Id.
     *
     * @param int $productId
     * @return void
     */
    public function deleteProductById(int $productId): void
    {
        if ($this->validateExistingKey(self::PRODUCT_ID_PREFIX . ":" . $productId . ":*")) {
            $this->redisConnection->del($this->getKeys(self::PRODUCT_ID_PREFIX . ":" . $productId . ":*"));
        }
    }

    /**
     * Delete Product By Slug.
     *
     * @param string $productSlug
     * @return void
     */
    public function deleteProductBySlug(string $productSlug): void
    {
        if ($this->validateExistingKey(self::PRODUCT_SLUG_PREFIX . ":" . $productSlug . ":*")) {
            $this->redisConnection->del($this->getKeys(self::PRODUCT_SLUG_PREFIX . ":" . $productSlug . ":*"));
        }
    }

    /**
     * Delete CrossSell By Id.
     *
     * @param int $productId
     * @return void
     */
    public function deleteCrossSellById(int $productId): void
    {
        if ($this->validateExistingKey(self::CROSS_SELL_ID_PREFIX . ":" . $productId . ":*")) {
            $this->redisConnection->del($this->getKeys(self::CROSS_SELL_ID_PREFIX . ":" . $productId . ":*"));
        }
    }

    /**
     * Delete CrossSell By Slug.
     *
     * @param string $productSlug
     * @return void
     */
    public function deleteCrossSellBySlug(string $productSlug): void
    {
        if ($this->validateExistingKey(self::CROSS_SELL_SLUG_PREFIX . ":" . $productSlug . ":*")) {
            $this->redisConnection->del($this->getKeys(self::CROSS_SELL_SLUG_PREFIX . ":" . $productSlug . ":*"));
        }
    }

    /**
     * Delete UpSell By Id.
     *
     * @param int $productId
     * @return void
     */
    public function deleteUpSellById(int $productId): void
    {
        if ($this->validateExistingKey(self::UPSELL_ID_PREFIX . ":" . $productId . ":*")) {
            $this->redisConnection->del($this->getKeys(self::UPSELL_ID_PREFIX . ":" . $productId . ":*"));
        }
    }

    /**
     * Delete UpSell By Slug.
     *
     * @param string $productSlug
     * @return void
     */
    public function deleteUpSellBySlug(string $productSlug): void
    {
        if ($this->validateExistingKey(self::UPSELL_SLUG_PREFIX . ":" . $productSlug . ":*")) {
            $this->redisConnection->del($this->getKeys(self::UPSELL_SLUG_PREFIX . ":" . $productSlug . ":*"));
        }
    }

    /**
     * Delete Related Products By Id.
     *
     * @param int $productId
     * @return void
     */
    public function deleteRelatedProductsById(int $productId): void
    {
        if ($this->validateExistingKey(self::RELATED_ID_PREFIX . ":" . $productId . ":*")) {
            $this->redisConnection->del($this->getKeys(self::RELATED_ID_PREFIX . ":" . $productId . ":*"));
        }
    }

    /**
     * Delete Related Products By Slug.
     *
     * @param string $productSlug
     * @return void
     */
    public function deleteRelatedProductsBySlug(string $productSlug): void
    {
        if ($this->validateExistingKey(self::RELATED_SLUG_PREFIX . ":" . $productSlug . ":*")) {
            $this->redisConnection->del($this->getKeys(self::RELATED_SLUG_PREFIX . ":" . $productSlug . ":*"));
        }
    }

    /**
     * Delete Analytics Cache.
     *
     * @param int $productId
     * @param string $sku
     * @param string $productSlug
     * @return void
     */
    public function deleteAnalytics(int $productId, string $sku, string $productSlug): void
    {
        if ($this->redisConnection) {
            if ($this->validateExistingKey(self::ANALYTICS_PRODUCT_ID_PREFIX . ":" . $productId . ":*")) {
                $this->redisConnection
                    ->del($this->getKeys(self::ANALYTICS_PRODUCT_ID_PREFIX . ":" . $productId . ":*"));
            }
            if ($this->validateExistingKey(self::ANALYTICS_PRODUCT_SKU_PREFIX . ":" . $sku . ":*")) {
                $this->redisConnection
                    ->del($this->getKeys(self::ANALYTICS_PRODUCT_SKU_PREFIX . ":" . $sku . ":*"));
            }
            if ($this->validateExistingKey(self::ANALYTICS_PRODUCT_SLUG_PREFIX . ":" . $productSlug . ":*")) {
                $this->redisConnection
                    ->del($this->getKeys(self::ANALYTICS_PRODUCT_SLUG_PREFIX . ":" . $productSlug . ":*"));
            }
        }
    }

    /**
     * Delete Category Cache
     *
     * @param int $categoryId
     * @return void
     */
    public function deleteCategory(int $categoryId): void
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            if ($this->redisConnection && $category->getUrlKey()) {
                $this->deleteCategoryById($categoryId);
                $this->deleteCategoryBySlug($category->getUrlKey());
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("deleteCategoryCache | Category ID: " . $categoryId
                . " cache clearing issue | " . $exception->getMessage() . " | Trace: "
                . $exception->getTraceAsString());
        }
    }

    /**
     * Delete Category By ID.
     *
     * @param int $categoryId
     * @return void
     */
    public function deleteCategoryById(int $categoryId): void
    {
        if ($this->validateExistingKey(self::CATEGORY_ID_PREFIX . ":" . $categoryId . ":*")) {
            $this->redisConnection->del($this->getKeys(self::CATEGORY_ID_PREFIX . ":" . $categoryId . ":*"));
        }
    }

    /**
     * Delete Category By Slug.
     *
     * @param string $categorySlug
     * @return void
     */
    public function deleteCategoryBySlug(string $categorySlug): void
    {
        if ($this->validateExistingKey(self::CATEGORY_SLUG_PREFIX . ":" . $categorySlug . ":*")) {
            $this->redisConnection->del($this->getKeys(self::CATEGORY_SLUG_PREFIX . ":" . $categorySlug . ":*"));
        }
    }

    /**
     * Get Parent Product ID.
     *
     * @param int $childProductId
     * @return false|mixed
     */
    private function getParentProductId(int $childProductId): mixed
    {
        $parentConfigProduct = $this->configurable->getParentIdsByChild($childProductId);
        if ($parentConfigProduct) {
            return $parentConfigProduct[0];
        }
        return false;
    }

    /**
     * Delete Menu API Cache
     *
     * @return void
     */
    public function deleteMenu(): void
    {
        if ($this->validateExistingKey(self::MENU_API_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::MENU_API_PREFIX . "*"));
        }
    }

    /**
     * Delete PLP Cache
     *
     * @param string $categorySlug
     * @return void
     */
    public function deletePlp(string $categorySlug = ""): void
    {
        if ($this->validateExistingKey(self::PLP_PREFIX . ":" . $categorySlug . "*")) {
            $this->redisConnection->del($this->getKeys(self::PLP_PREFIX . ":" . $categorySlug . "*"));
        }
    }

    /**
     * Delete Search Cache
     *
     * @return void
     */
    public function deleteSearch(): void
    {
        if ($this->validateExistingKey(self::SEARCH_PREFIX_V1 . "*")) {
            $this->redisConnection->del($this->getKeys(self::SEARCH_PREFIX_V1 . "*"));
        }
        if ($this->validateExistingKey(self::SEARCH_PREFIX_V2 . "*")) {
            $this->redisConnection->del($this->getKeys(self::SEARCH_PREFIX_V2 . "*"));
        }
    }

    /**
     * Delete Search Term Cache
     *
     * @param string $searchTerm
     * @return void
     */
    public function deleteSearchTerm(string $searchTerm = ""): void
    {
        if ($this->validateExistingKey(self::SEARCH_TERM_PREFIX_V1 . ":" . $searchTerm)) {
            $this->redisConnection->del($this->getKeys(self::SEARCH_TERM_PREFIX_V1 . ":" . $searchTerm));
        }
        if ($this->validateExistingKey(self::SEARCH_TERM_PREFIX_V2 . ":" . $searchTerm)) {
            $this->redisConnection->del($this->getKeys(self::SEARCH_TERM_PREFIX_V2 . ":" . $searchTerm));
        }
    }

    /**
     * Delete Category (L1/L2) List API Cache
     *
     * @return void
     */
    public function deleteCategoryList(): void
    {
        if ($this->validateExistingKey(self::CATEGORIES)) {
            $this->redisConnection->del($this->getKeys(self::CATEGORIES));
        }
    }

    /**
     * Delete Category Mapping Cache
     *
     * @return void
     */
    public function deleteCategoryMapping(): void
    {
        if ($this->validateExistingKey(self::CATEGORY_MAPPING_PREFIX)) {
            $this->redisConnection->del($this->getKeys(self::CATEGORY_MAPPING_PREFIX));
        }
    }

    /**
     * Delete All PDP Cache.
     *
     * @return void
     */
    public function clearAllPdp(): void
    {
        if ($this->validateExistingKey(self::PRODUCT_ID_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::PRODUCT_ID_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::PRODUCT_SLUG_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::PRODUCT_SLUG_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::CROSS_SELL_ID_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::CROSS_SELL_ID_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::CROSS_SELL_SLUG_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::CROSS_SELL_SLUG_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::UPSELL_ID_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::UPSELL_ID_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::UPSELL_SLUG_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::UPSELL_SLUG_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::RELATED_ID_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::RELATED_ID_PREFIX . "*"));
        }

        if ($this->validateExistingKey(self::RELATED_SLUG_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::RELATED_SLUG_PREFIX . "*"));
        }
    }

    /**
     * Delete Attribute Filters Position Cache
     *
     * @return void
     */
    public function deleteFiltersPosition(): void
    {
        if ($this->validateExistingKey(self::FILTERS_POSITION_PREFIX)) {
            $this->redisConnection->del($this->getKeys(self::FILTERS_POSITION_PREFIX));
        }
    }

    /**
     * Delete Product Attributes Mapping Cache
     *
     * @return void
     */
    public function deleteProductAttributesMapping(): void
    {
        if ($this->validateExistingKey(self::PRODUCT_ATTRIBUTE_MAPPING_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::PRODUCT_ATTRIBUTE_MAPPING_PREFIX . "*"));
        }
    }

    /**
     * Delete External API Response
     *
     * @return void
     */
    public function deleteExternalCatalog(): void
    {
        foreach (self::EXTERNAL_PLATFORM as $platform) {
            if ($this->validateExistingKey(self::CATEGORY_ID_PREFIX . "*" . $platform . "*")) {
                $this->redisConnection->del($this->getKeys(self::CATEGORY_ID_PREFIX . "*" . $platform . "*"));
            }
            if ($this->validateExistingKey(self::PRODUCT_ID_PREFIX . "*" . $platform . "*")) {
                $this->redisConnection->del($this->getKeys(self::PRODUCT_ID_PREFIX . "*" . $platform . "*"));
            }
        }
    }

    /**
     * Delete Banner Cache
     *
     * @return void
     */
    public function deleteBanner(): void
    {
        if ($this->validateExistingKey(self::BANNER_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::BANNER_PREFIX . "*"));
        }
    }

    /**
     * Delete Product Carousel Cache
     *
     * @return void
     */
    public function deleteCarousel(): void
    {
        if ($this->validateExistingKey(self::CATEGORY_SLUG_PREFIX . "*")) {
            $this->redisConnection->del($this->getKeys(self::CATEGORY_SLUG_PREFIX . "*"));
        }
    }

    /**
     * Delete Category Info Cache
     *
     * @param string $categorySlug
     * @return void
     */
    public function deleteCategoryInfo(string $categorySlug = ""): void
    {
        if ($this->validateExistingKey(self::CATEGORY_INFO_SLUG . ":" . $categorySlug . "*")) {
            $this->redisConnection->del($this->getKeys(self::CATEGORY_INFO_SLUG . ":" . $categorySlug . "*"));
        }
    }

    /**
     * Delete Shop By Cache
     *
     * @param string $categorySlug
     * @return void
     */
    public function deleteShopBy(string $categorySlug = ""): void
    {
        if ($this->validateExistingKey(self::SHOP_BY_CATEGORY . ":" . $categorySlug)) {
            $this->redisConnection->del($this->getKeys(self::SHOP_BY_CATEGORY . ":" . $categorySlug));
        }
    }

    /**
     * Delete All Products Offer Cache
     *
     * @return void
     */
    public function deleteAllProductsOffer(): void
    {
        try {
            if ($this->redisConnection && count($this->getKeys(self::PRODUCTS_OFFER_SLUG_PREFIX . "*"))) {
                $this->redisConnection->del($this->getKeys(self::PRODUCTS_OFFER_SLUG_PREFIX . "*"));
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("deleteAllProductsOfferCache | Cache clearing issue | "
                . $exception->getMessage() . " | Trace: " . $exception->getTraceAsString());
        }
    }

    /**
     * Delete Products Offer Cache
     *
     * @param int $productId
     * @return void
     */
    public function deleteProductsOffer(int $productId): void
    {
        try {
            if ($this->redisConnection) {
                $product = $this->productRepository->getById($productId);
                $productSlug = $product->getUrlKey();

                if (count($this->getKeys(self::PRODUCTS_OFFER_SLUG_PREFIX . ":" . $productSlug . "*"))) {
                    $this->redisConnection->del(self::PRODUCTS_OFFER_SLUG_PREFIX . ":" . $productSlug . "*");
                }
            }
        } catch (Exception $exception) {
            $this->redisCacheLogger->error("deleteProductsOfferCache | Product ID: " . $productId
                . " cache clearing issue | " . $exception->getMessage() . " | Trace: "
                . $exception->getTraceAsString());
        }
    }
}
