<?php

namespace Pratech\Catalog\Model\Adminhtml\System\Config\Source\Product;

/**
 * Product Attribute Lists
 */
class Attributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attributeFactory;

    /**
     * Attributes Class Constructor
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
    ) {
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $attributesData = [];
        $attributeInfo = $this->attributeFactory->getCollection()->addFieldToFilter('entity_type_id', ['eq' => 4]);

        foreach ($attributeInfo as $attributes) {
            $attribute['value'] = $attributes->getAttributeCode();
            $attribute['label'] = $attributes->getFrontendLabel();
            $attributesData[] = $attribute;
        }

        return $attributesData;
    }
}
