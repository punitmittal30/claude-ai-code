<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Pratech\Warehouse\Api\Data\AttributeFilterInterface;

/**
 * Attribute filter implementation
 */
class AttributeFilter extends AbstractSimpleObject implements AttributeFilterInterface
{
    /**
     * @inheritDoc
     */
    public function getAttributeId(): int
    {
        return (int)$this->_get('attribute_id');
    }

    /**
     * @inheritDoc
     */
    public function setAttributeId(int $attributeId): self
    {
        return $this->setData('attribute_id', $attributeId);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeCode(): string
    {
        return $this->_get('attribute_code');
    }

    /**
     * @inheritDoc
     */
    public function setAttributeCode(string $attributeCode): self
    {
        return $this->setData('attribute_code', $attributeCode);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeLabel(): string
    {
        return $this->_get('attribute_label');
    }

    /**
     * @inheritDoc
     */
    public function setAttributeLabel($attributeLabel): self
    {
        // Convert Magento Phrase objects to string
        if ($attributeLabel instanceof \Magento\Framework\Phrase) {
            $attributeLabel = (string)$attributeLabel;
        }

        return $this->setData('attribute_label', $attributeLabel);
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return $this->_get('options') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options): self
    {
        return $this->setData('options', $options);
    }
}
