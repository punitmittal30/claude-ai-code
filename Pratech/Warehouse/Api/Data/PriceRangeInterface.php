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
 * Interface for price range filter
 */
interface PriceRangeInterface
{
    /**
     * Get from price
     *
     * @return float
     */
    public function getFrom(): float;

    /**
     * Set from price
     *
     * @param float $from
     * @return $this
     */
    public function setFrom(float $from): self;

    /**
     * Get to price
     *
     * @return float
     */
    public function getTo(): float;

    /**
     * Set to price
     *
     * @param float $to
     * @return $this
     */
    public function setTo(float $to): self;

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
}
