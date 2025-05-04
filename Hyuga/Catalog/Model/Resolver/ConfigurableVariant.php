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

namespace Hyuga\Catalog\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Type;
use Magento\ConfigurableProductGraphQl\Model\Options\Collection as OptionCollection;
use Magento\ConfigurableProductGraphQl\Model\Variant\Collection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Custom Configurable Variant Resolver
 */
class ConfigurableVariant implements ResolverInterface
{
    /**
     * @param Collection $variantCollection
     * @param OptionCollection $optionCollection
     * @param ValueFactory $valueFactory
     * @param MetadataPool $metadataPool
     * @param FieldSelection $fieldSelection
     * @param DeliveryDateCalculator $deliveryDateCalculator
     */
    public function __construct(
        private Collection             $variantCollection,
        private OptionCollection       $optionCollection,
        private ValueFactory           $valueFactory,
        private MetadataPool           $metadataPool,
        private FieldSelection         $fieldSelection,
        private DeliveryDateCalculator $deliveryDateCalculator
    ) {
    }

    /**
     * Resolve configurable child products with sorting and estimated delivery time
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        if ($value['type_id'] !== Type::TYPE_CODE || !isset($value[$linkField])) {
            return $this->valueFactory->create(fn() => null);
        }

        $this->variantCollection->addParentProduct($value['model']);
        $fields = $this->fieldSelection->getProductsFieldSelection($info);
        $this->variantCollection->addEavAttributes($fields);
        $this->optionCollection->addProductId((int)$value[$linkField]);

        $result = function () use ($value, $linkField, $context, $args) {
            $children = $this->variantCollection->getChildProductsByParentId((int)$value[$linkField], $context);
            $options = $this->optionCollection->getAttributesByProductId((int)$value[$linkField]);

            $pincode = $context->getExtensionAttributes()->getPincode();
            foreach ($children as &$child) {
                $deliveryData = $this->deliveryDateCalculator->getEstimatedDelivery($child['sku'], $pincode);
                $child['estimate_delivery'] = isset($deliveryData['delivery_time'])
                    ? $deliveryData['delivery_time']
                    : PHP_INT_MAX;
            }

            // Sort by highest sold
            usort($children, fn($a, $b) => (int)$b['model']->getData('qty_sold')
                <=> (int)$a['model']->getData('qty_sold'));
            $sortedChildren = [array_shift($children)];

            // Sort fastest delivery
            if (!empty($children)) {
                usort(
                    $children,
                    fn($a, $b) => (int)($a['estimate_delivery'] ?? PHP_INT_MAX)
                        <=> (int)($b['estimate_delivery'] ?? PHP_INT_MAX)
                );
                $sortedChildren[] = array_shift($children);
            }

            // Sort the lowest price
            if (!empty($children)) {
                usort($children, fn($a, $b) => (float)$a['model']->getFinalPrice()
                    <=> (float)$b['model']->getFinalPrice());
                $sortedChildren[] = array_shift($children);
            }

            $sortedChildren = array_merge($sortedChildren, $children);

            $variants = [];
            foreach ($sortedChildren as $key => $sortedChild) {
                $variants[$key] = [
                    'sku' => $sortedChild['sku'],
                    'product' => $sortedChild,
                    'options' => $options,
                ];
            }

            return $variants;
        };
        return $this->valueFactory->create($result);
    }
}
