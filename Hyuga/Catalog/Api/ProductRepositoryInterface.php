<?php

namespace Hyuga\Catalog\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface ProductRepositoryInterface
{
    /**
     * Get product data by product id.
     *
     * @param int $productId
     * @param int|null $pincode
     * @param string $section
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductById(int $productId, int $pincode = null, string $section = ''): array;

    /**
     * Get product data by product url.
     *
     * @param string $slug
     * @param int|null $pincode
     * @param string $section
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductBySlug(string $slug, int $pincode = null, string $section = ''): array;

}
