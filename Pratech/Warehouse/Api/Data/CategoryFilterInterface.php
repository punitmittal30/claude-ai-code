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
 * Interface for category filter
 */
interface CategoryFilterInterface
{
    /**
     * Get category ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set category ID
     *
     * @param int $id
     * @return $this
     */
    public function setId(int $id): self;

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Set label
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
