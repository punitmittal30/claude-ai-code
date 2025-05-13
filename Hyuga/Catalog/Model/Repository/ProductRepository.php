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
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Product Helper Constructor
     *
     * @param RestApiProductAttributeService $productAttributeService
     * @param ProductFactory $productFactory
     */
    public function __construct(
        private RestApiProductAttributeService $productAttributeService,
        private ProductFactory                 $productFactory
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProductById(int $productId, int $pincode = null, string $section = ''): array
    {
        return $this->productAttributeService->getAttributes($productId, $pincode, $section);
    }

    /**
     * @inheritDoc
     */
    public function getProductBySlug(string $productSlug, int $pincode = null, string $section = ''): array
    {
        $productId = $this->getProductIdByUrl($productSlug);
        return $this->productAttributeService->getAttributes($productId, $pincode, $section);
    }

    /**
     * Get Product ID By Product Slug
     *
     * @param string $url
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getProductIdByUrl(string $url): ?int
    {
        $product = $this->productFactory->create()->loadByAttribute('url_key', $url);

        if ($product) {
            return $product->getId();
        } else {
            throw new NoSuchEntityException(__('The product that was requested does not exists'));
        }
    }
}
