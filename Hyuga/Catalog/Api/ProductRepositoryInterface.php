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
}
