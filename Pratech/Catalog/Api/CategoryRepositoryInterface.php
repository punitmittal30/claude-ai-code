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

/**
 * Category Repository Interface to expose categories api.
 */
interface CategoryRepositoryInterface
{
    /**
     * Get Categories
     *
     * @param string $categorySlug
     * @return \Pratech\Catalog\Api\Data\CategoryTreeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryTree(string $categorySlug);

    /**
     * Get Products By Category ID
     *
     * @param int $categoryId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductsByCategoryId(int $categoryId, int $pincode = null): array;

    /**
     * Get Products By Category Slug
     *
     * @param string $slug
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductsByCategorySlug(string $slug, int $pincode = null): array;

    /**
     * Get Category Bubbles
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryBubbles(): array;

    /**
     * Get Menu Items
     *
     * @param string $categorySlug
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryInfo(string $categorySlug): array;

    /**
     * Get Sub Categories By Id
     *
     * @param int $categoryId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubCategoriesById(int $categoryId): array;

    /**
     * Get "Shop By Category for PLP" By Category Slug
     *
     * @param string $categorySlug
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShopByCategoryForPLP(string $categorySlug): array;

    /**
     * Get "Top brand Deals for PLP" By Category Slug
     *
     * @param string $categorySlug
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTopBrandDealsForPLP(string $categorySlug): array;

    /**
     * Return Category Details based on input provided.
     *
     * @param string $searchTerm
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function searchCategories(string $searchTerm): array;

    /**
     * Get Categories List with Type (L1 or L2)
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoriesList(): array;
}
