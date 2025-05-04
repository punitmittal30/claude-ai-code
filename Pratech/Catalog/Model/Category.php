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

use Pratech\Base\Model\Data\Response;
use Pratech\Catalog\Api\CategoryRepositoryInterface;
use Pratech\Catalog\Helper\Product as ProductHelper;

/**
 * Category class to expose categories endpoint
 */
class Category implements CategoryRepositoryInterface
{
    /**
     * Constant for CATEGORY API RESOURCE
     */
    public const CATEGORY_API_RESOURCE = 'category';

    /**
     * Category Constructor
     *
     * @param Response $response
     * @param ProductHelper $categoryHelper
     */
    public function __construct(
        private Response      $response,
        private ProductHelper $categoryHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCategoryTree(string $categorySlug)
    {
        return $this->categoryHelper->getCategoryTree($categorySlug);
    }

    /**
     * @inheritDoc
     */
    public function getProductsByCategoryId(int $categoryId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getCategoryProductsById($categoryId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getProductsByCategorySlug(string $slug, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getCategoryProductsBySlug($slug, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCategoryBubbles(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getCategoryBubbles()
        );
    }

    /**
     * @inheritDoc
     */
    public function getCategoryInfo(string $categorySlug): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getCategoryInfo($categorySlug)
        );
    }

    /**
     * @inheritDoc
     */
    public function getSubCategoriesById(int $categoryId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getSubCategoriesById($categoryId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getShopByCategoryForPLP(string $categorySlug): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getShopByCategoryForPLP($categorySlug)
        );
    }

    /**
     * @inheritDoc
     */
    public function getTopBrandDealsForPLP(string $categorySlug): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getTopBrandDealsForPLP($categorySlug)
        );
    }

    /**
     * @inheritDoc
     */
    public function searchCategories(string $searchTerm): array
    {
        return $this->categoryHelper->searchCategories($searchTerm);
    }

    /**
     * @inheritDoc
     */
    public function getCategoriesList(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CATEGORY_API_RESOURCE,
            $this->categoryHelper->getCategoriesList()
        );
    }
}
