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

namespace Pratech\Catalog\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Model\Data\Response;
use Pratech\Catalog\Api\ProductRepositoryInterface;

/**
 * Product class to expose catalog api endpoints.
 */
class Product implements ProductRepositoryInterface
{
    public const PRODUCT_API_RESOURCE = 'product';

    public const SEARCH_API_RESOURCE = 'search';

    /**
     * Product constructor
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Pratech\Catalog\Helper\Product $productHelper
     * @param Response $response
     * @param Logger $apiLogger
     */
    public function __construct(
        private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private \Pratech\Catalog\Helper\Product                 $productHelper,
        private Response                                        $response,
        private Logger                                          $apiLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProducts($searchCriteria): array
    {
        $products = [];
        $product = $this->productRepository->getList($searchCriteria);
        foreach ($product->getItems() as $item) {
            $products[] = $this->productHelper->formatProductForPDP($item->getId());
        }
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $products
        );
    }

    /**
     * @inheritDoc
     */
    public function getUpSellProductsBySlug(string $slug, int $pincode = null): array
    {
        $upsellProducts = [];
        try {
            $productId = $this->productHelper->getProductIdByUrl($slug);
            $upsellProducts = $this->productHelper->getRecommendedProductsByID($productId, 'up_sell', $pincode);
        } catch (NoSuchEntityException $exception) {
            $this->apiLogger->error("Error | Fetch Product URL By Product ID | Product Slug: " . $slug . " | "
                . __METHOD__ . " | " . $exception->getMessage());
        }
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $upsellProducts
        );
    }

    /**
     * @param string $slug
     * @param int|null $pincode
     * @inheritDoc
     */
    public function getProductBySlug(string $slug, int $pincode = null): array
    {
        $productId = $this->productHelper->getProductIdByUrl($slug);
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->formatProductForPDP($productId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductOffersBySlug(string $slug, string $platform = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getProductOffersBySlug($slug, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCrossSellProductsBySlug(string $slug, int $pincode = null): array
    {
        $crossSellProducts = [];
        try {
            $productId = $this->productHelper->getProductIdByUrl($slug);
            $crossSellProducts = $this->productHelper->getRecommendedProductsByID($productId, 'cross_sell', $pincode);
        } catch (NoSuchEntityException $exception) {
            $this->apiLogger->error("Error | Fetch Product URL By Product ID | Product Slug: " . $slug . " | "
                . __METHOD__ . " | " . $exception->getMessage());
        }
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $crossSellProducts
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelatedProductsBySlug(string $slug, int $pincode = null): array
    {
        $relatedProducts = [];
        try {
            $productId = $this->productHelper->getProductIdByUrl($slug);
            $relatedProducts = $this->productHelper->getRecommendedProductsByID($productId, 'related', $pincode);
        } catch (NoSuchEntityException $exception) {
            $this->apiLogger->error("Error | Fetch Product URL By Product ID | Product Slug: " . $slug . " | "
                . __METHOD__ . " | " . $exception->getMessage());
        }
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $relatedProducts
        );
    }

    /**
     * @inheritDoc
     */
    public function getUpSellProductsById(int $productId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getRecommendedProductsByID($productId, 'up_sell', $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductById(int $productId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->formatProductForPDP($productId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCrossSellProductsById(int $productId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getRecommendedProductsByID($productId, 'cross_sell', $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelatedProductsById(int $productId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getRecommendedProductsByID($productId, 'related', $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function quickSearch(\Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::SEARCH_API_RESOURCE,
            $this->productHelper->quickSearch($searchCriteria)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductBySlugForAnalytics(string $slug): array
    {
        $productId = $this->productHelper->getProductIdByUrl($slug);
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getProductInfoBySlug($productId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductByIdForAnalytics(int $productId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getProductInfoById($productId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductBySkuForAnalytics(string $sku): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getProductInfoBySku($sku)
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributesMapping(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::SEARCH_API_RESOURCE,
            $this->productHelper->getAttributesMapping()
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerWidgets(int $customerId, int $productCount): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::PRODUCT_API_RESOURCE,
            $this->productHelper->getCustomerWidgets($customerId, $productCount)
        );
    }
}
