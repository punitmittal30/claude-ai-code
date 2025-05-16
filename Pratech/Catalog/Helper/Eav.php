<?php

namespace Pratech\Catalog\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;

/**
 * Eav Helper Class to get attribute values.
 */
class Eav
{
    /**
     * Product Entity Type
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * @param Logger $logger
     * @param Attribute $attributeResource
     * @param Config $eavConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private Logger               $logger,
        private Attribute            $attributeResource,
        private Config               $eavConfig,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get Attributes in Label Value Format
     *
     * @param ProductInterface $product
     * @param string $configPath
     * @return array
     */
    public function getLabelValueFormat(ProductInterface $product, string $configPath): array
    {
        $list = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
        $attributes = $list !== null ? explode(',', $list) : [];
        $result = [];
        foreach ($attributes as $attributeCode) {
            if ($product->getCustomAttribute($attributeCode)) {
                if (in_array($attributeCode, $this->getProductAttributesWithDropdown())) {
                    $value = $this->getOptionLabel(
                        $attributeCode,
                        $product->getCustomAttribute($attributeCode)->getValue()
                    );
                } elseif (in_array($attributeCode, $this->getProductAttributesWithBoolean())) {
                    $value = $product->getCustomAttribute($attributeCode)->getValue() ? "Yes" : "No";
                } elseif (in_array($attributeCode, $this->getProductAttributesWithMultiselect())) {
                    $values = [];
                    $attributeValues = explode(',', $product->getCustomAttribute($attributeCode)->getValue());
                    foreach ($attributeValues as $attributeValue) {
                        $values[] = $this->getOptionLabel(
                            $attributeCode,
                            $attributeValue
                        );
                    }
                    $value = implode(', ', $values);

                } else {
                    $value = $product->getCustomAttribute($attributeCode)->getValue();
                }
                $result[] = [
                    "label" => $product->getResource()->getAttribute($attributeCode)->getFrontendLabel(),
                    "value" => $value,
                    "attribute_code" => $attributeCode
                ];
            }
        }
        return $result;
    }

    /**
     * Get Product Attributes having type as select
     *
     * @return array
     */
    private function getProductAttributesWithDropdown(): array
    {
        try {
            $connection = $this->attributeResource->getConnection();
            $select = $connection->select()
                ->from($this->attributeResource->getMainTable(), 'attribute_code')
                ->where('entity_type_id = ?', 4)
                ->where('frontend_input = ?', 'select');
            return $connection->fetchCol($select);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Attribute Option Label using Attribute Value
     *
     * @param string $attributeCode
     * @param string $optionValue
     * @return mixed|string
     */
    public function getOptionLabel(string $attributeCode, string $optionValue)
    {
        $optionArray = $this->getAttributeOptions($attributeCode);
        foreach ($optionArray as $options) {
            if ($options['value'] == $optionValue) {
                return $options['label'];
            }
        }
        return "";
    }

    /**
     * Get Multiselect Attribute Options Label using Attribute Value
     *
     * @param string $attributeCode
     * @param string $optionValues
     * @return mixed|string
     */
    public function getMultiselectOptionsLabel(string $attributeCode, string $optionValues)
    {
        $optionsLabel = [];
        $optionValuesArray = explode(',', $optionValues);
        if ($optionValuesArray) {
            foreach ($optionValuesArray as $optionValue) {
                $optionsLabel[] = $this->getOptionLabel(
                    $attributeCode,
                    $optionValue
                );
            }
            return implode(', ', $optionsLabel);
        }
        return "";
    }

    /**
     * Get Attribute Options
     *
     * @param string $attributeCode
     * @return array
     */
    public function getAttributeOptions(string $attributeCode): array
    {
        try {
            $attribute = $this->eavConfig->getAttribute(self::ENTITY_TYPE, $attributeCode);
            $options = $attribute->getSource()->getAllOptions();
            $attributeOptions = [];
            foreach ($options as $option) {
                if ($option['value'] > 0) {
                    $attributeOptions[] = $option;
                }
            }
            return $attributeOptions;
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Product Attributes having type as select
     *
     * @return array
     */
    private function getProductAttributesWithBoolean(): array
    {
        try {
            $connection = $this->attributeResource->getConnection();
            $select = $connection->select()
                ->from($this->attributeResource->getMainTable(), 'attribute_code')
                ->where('entity_type_id = ?', 4)
                ->where('frontend_input = ?', 'boolean');
            return $connection->fetchCol($select);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Product Attributes having type as select
     *
     * @return array
     */
    private function getProductAttributesWithMultiselect(): array
    {
        try {
            $connection = $this->attributeResource->getConnection();
            $select = $connection->select()
                ->from($this->attributeResource->getMainTable(), 'attribute_code')
                ->where('entity_type_id = ?', 4)
                ->where('frontend_input = ?', 'multiselect');
            return $connection->fetchCol($select);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Scope Config Value.
     *
     * @param string $config
     * @return mixed
     */
    public function getConfigValue(string $config): mixed
    {
        return $this->scopeConfig->getValue(
            $config,
            ScopeInterface::SCOPE_STORE
        );
    }
}
