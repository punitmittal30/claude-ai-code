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

namespace Pratech\Warehouse\Api\Data;

/**
 * Interface for attribute filter
 */
interface AttributeFilterInterface
{
    /**
     * Get attribute ID
     *
     * @return int
     */
    public function getAttributeId(): int;

    /**
     * Set attribute ID
     *
     * @param int $attributeId
     * @return $this
     */
    public function setAttributeId(int $attributeId): self;

    /**
     * Get attribute code
     *
     * @return string
     */
    public function getAttributeCode(): string;

    /**
     * Set attribute code
     *
     * @param string $attributeCode
     * @return $this
     */
    public function setAttributeCode(string $attributeCode): self;

    /**
     * Get attribute label
     *
     * @return string
     */
    public function getAttributeLabel(): string;

    /**
     * Set attribute label
     *
     * @param string|\Magento\Framework\Phrase $attributeLabel
     * @return $this
     */
    public function setAttributeLabel($attributeLabel): self;

    /**
     * Get attribute options
     *
     * @return \Pratech\Warehouse\Api\Data\AttributeOptionInterface[]
     */
    public function getOptions(): array;

    /**
     * Set attribute options
     *
     * @param \Pratech\Warehouse\Api\Data\AttributeOptionInterface[] $options
     * @return $this
     */
    public function setOptions(array $options): self;
}
