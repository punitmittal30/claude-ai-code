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

namespace Pratech\Catalog\Api;

interface ProductRepositoryInterface
{
    /**
     * Get product list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProducts(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array;

    /**
     * Get info about product by product id
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById(int $productId, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductBySlug(string $slug, int $pincode = null): array;

    /**
     * Get Product Offers by product slug
     *
     * @param string $slug
     * @param string|null $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductOffersBySlug(string $slug, string $platform = null): array;

    /**
     * Get info about product by product id
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUpSellProductsBySlug(string $slug, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCrossSellProductsBySlug(string $slug, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRelatedProductsBySlug(string $slug, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUpSellProductsById(int $productId, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCrossSellProductsById(int $productId, int $pincode = null): array;

    /**
     * Get info about product by product id
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRelatedProductsById(int $productId, int $pincode = null): array;

    /**
     * Quick Search API
     *
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function quickSearch(\Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria): array;

    /**
     * Get info about product by product slug for analytics
     *
     * @param string $slug
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductBySlugForAnalytics(string $slug): array;

    /**
     * Get info about product by product id for analytics
     *
     * @param int $productId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductByIdForAnalytics(int $productId): array;

    /**
     * Get info about product by product id for analytics
     *
     * @param string $sku
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductBySkuForAnalytics(string $sku): array;

    /**
     * Attributes Mapping API
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttributesMapping(): array;

    /**
     * Get Customer Widgets Data
     *
     * @return array
     * @param int $customerId
     * @param int $productCount
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerWidgets(int $customerId, int $productCount): array;
}
