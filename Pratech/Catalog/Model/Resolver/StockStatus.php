<?php

namespace Pratech\Catalog\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class StockStatus implements ResolverInterface
{

    /**
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        private StockRegistryInterface $stockRegistry
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

        return $stockStatus->getIsInStock() ? 'IN_STOCK' : 'OUT_OF_STOCK';
    }
}
