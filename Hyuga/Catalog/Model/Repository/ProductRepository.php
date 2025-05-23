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

namespace Hyuga\Catalog\Model\Repository;

use Hyuga\Catalog\Api\ProductRepositoryInterface;
use Hyuga\Catalog\Service\RestApiProductAttributeService;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Product Helper Constructor
     *
     * @param RestApiProductAttributeService $productAttributeService
     */
    public function __construct(
        private RestApiProductAttributeService $productAttributeService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProductById(int $productId, int $pincode = null, string $section = ''): array
    {
        return $this->productAttributeService->getAttributes($productId, $pincode);
    }
}
