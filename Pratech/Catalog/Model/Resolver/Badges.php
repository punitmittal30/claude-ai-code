<?php

namespace Pratech\Catalog\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Catalog\Helper\Eav;

class Badges implements ResolverInterface
{
    /**
     * @param Eav $eavHelper
     */
    public function __construct(
        private Eav $eavHelper,
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

        if ($product->getCustomAttribute('badges')) {
            return $this->eavHelper->getMultiselectOptionsLabel(
                'badges',
                $product->getCustomAttribute('badges')->getValue()
            );
        }
        return "";
    }
}
