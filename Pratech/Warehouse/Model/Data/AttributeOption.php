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
use Pratech\Warehouse\Api\Data\AttributeOptionInterface;

/**
 * Attribute option implementation
 */
class AttributeOption extends AbstractSimpleObject implements AttributeOptionInterface
{
    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return (string)$this->_get('value');
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): self
    {
        return $this->setData('value', $value);
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->_get('label');
    }

    /**
     * @inheritDoc
     */
    public function setLabel($label): self
    {
        // Convert Magento Phrase objects to string
        if ($label instanceof \Magento\Framework\Phrase) {
            $label = (string)$label;
        }

        return $this->setData('label', $label);
    }

    /**
     * @inheritDoc
     */
    public function getCount(): int
    {
        return (int)$this->_get('count');
    }

    /**
     * @inheritDoc
     */
    public function setCount(int $count): self
    {
        return $this->setData('count', $count);
    }
}
