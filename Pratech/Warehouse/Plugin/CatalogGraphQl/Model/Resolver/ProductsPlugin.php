<?php

namespace Pratech\Warehouse\Plugin\CatalogGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products as ProductsResolver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ProductsPlugin
{
    /**
     * Before plugin for Products resolver
     *
     * @param ProductsResolver $subject
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        ProductsResolver $subject,
        Field $field,
                         $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($args['pincode'])) {
            $context->getExtensionAttributes()->setPincode($args['pincode']);
        }

        return [$field, $context, $info, $value, $args];
    }
}
