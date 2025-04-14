<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Framework\Option\ArrayInterface;

class AttributeList implements ArrayInterface
{
    public function __construct(
        private AttributeCollection $attributeCollection
    ) {
    }

    public function toOptionArray(): array
    {
        $attributeCollection = $this->attributeCollection->addFieldToFilter('is_filterable', 1)
            ->setOrder('frontend_label', 'ASC');

        $list[] = [
            'label' => "Select Filter Attribute",
            'value' => ""
        ];
        foreach ($attributeCollection as $attribute) {
            $list[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel()
            ];
        }

        return $list;
    }
}
