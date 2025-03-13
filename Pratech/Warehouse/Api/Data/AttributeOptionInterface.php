<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Api\Data;

/**
 * Interface for attribute option
 */
interface AttributeOptionInterface
{
    /**
     * Get option value
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Set option value
     *
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self;

    /**
     * Get option label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Set option label
     *
     * @param string|\Magento\Framework\Phrase $label
     * @return $this
     */
    public function setLabel($label): self;

    /**
     * Get product count
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Set product count
     *
     * @param int $count
     * @return $this
     */
    public function setCount(int $count): self;
}
