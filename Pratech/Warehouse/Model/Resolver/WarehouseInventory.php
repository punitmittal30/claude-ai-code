<?php
namespace Pratech\Warehouse\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\Data\ProductInterface;

class WarehouseInventory implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
              $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model']) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $product = $value['model'];

        $qty = $product->hasData('inventory_qty')
            ? (int)$product->getData('inventory_qty')
            : 0;

        $isInStock = $product->hasData('inventory_is_in_stock')
            && (bool)$product->getData('inventory_is_in_stock');

        // Return as object structure
        return [
            'inventory_qty' => $qty,
            'inventory_is_in_stock' => $isInStock
        ];
    }
}
