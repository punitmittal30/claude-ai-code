<?php

namespace Pratech\Warehouse\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class WarehouseInventory implements ResolverInterface
{
    /**
     * @param StockRegistryInterface $stockRegistry
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private StockRegistryInterface $stockRegistry,
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        if (!isset($value['model']) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $attributeCode = $field->getName();
        $product = $value['model'];
        $productData = $this->productRepository->getById($product->getId());

        $qty = $product->hasData('inventory_qty')
            ? (int)$product->getData('inventory_qty')
            : 0;

        $isInStock = $product->hasData('inventory_is_in_stock')
            && (bool)$product->getData('inventory_is_in_stock');

        if ($attributeCode == 'stock_status') {
            $isDropship = (int)$productData->getCustomAttribute('is_dropship')?->getValue();
            if ($isDropship) {
                $stockStatus = $this->stockRegistry->getStockItem($product->getId());
                return $stockStatus->getIsInStock() ? 'IN_STOCK' : 'OUT_OF_STOCK';
            }
            return $isInStock ? 'IN_STOCK' : 'OUT_OF_STOCK';
        }

        // Return as object structure
        return [
            'inventory_qty' => $qty,
            'inventory_is_in_stock' => $isInStock
        ];
    }
}
