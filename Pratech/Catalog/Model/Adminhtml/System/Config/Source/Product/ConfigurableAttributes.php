<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Catalog\Model\Adminhtml\System\Config\Source\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler;

/**
 * Product Attribute Lists
 */
class ConfigurableAttributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * ConfigurableAttributes Class Constructor
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param ConfigurableAttributeHandler $configurableAttributeHandler
     */
    public function __construct(
        protected AttributeCollectionFactory $attributeCollectionFactory,
        protected ConfigurableAttributeHandler $configurableAttributeHandler
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $attributesData = [];
         /** @var $attributeCollection \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
        $attributeCollection = $this->configurableAttributeHandler->getApplicableAttributes();
        foreach ($attributeCollection->getItems() as $id => $attribute) {
            if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
                /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
                $attribute['value'] = $attribute->getAttributeCode();
                $attribute['label'] = $attribute->getFrontendLabel();
                $attributesData[] = $attribute;
            }
        }

        return $attributesData;
    }
}
