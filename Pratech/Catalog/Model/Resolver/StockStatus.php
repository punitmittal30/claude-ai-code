<?php

namespace Pratech\Catalog\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Warehouse\Service\InventoryLocatorService;

class StockStatus implements ResolverInterface
{
    /**
     * @param StockRegistryInterface $stockRegistry
     * @param InventoryLocatorService $inventoryLocatorService
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private StockRegistryInterface     $stockRegistry,
        private InventoryLocatorService    $inventoryLocatorService,
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $product = $value['model'];

        $stockStatus = $this->stockRegistry->getStockItem($product->getId());
//        $pincode = $context->getExtensionAttributes()->getPincode();
//        $productData = $this->productRepository->getById($product->getId());
//
//        if ($productData->getCustomAttribute('is_dropship')?->getValue()) {
//            $isInventoryInStock = $stockStatus->getIsInStock() && $productData->getStatus() == 1;
//        } else {
//            $inventoryQty = $this->inventoryLocatorService
//                ->getInventoryQtyByPincode($productData->getSku(), $pincode);
//            $isInventoryInStock = $inventoryQty > 0 && $stockStatus->getIsInStock()
//                && $productData->getStatus() == 1;
//        }

        return $stockStatus->getIsInStock() ? 'IN_STOCK' : 'OUT_OF_STOCK';
//        return $isInventoryInStock ? 'IN_STOCK' : 'OUT_OF_STOCK';
    }
}
