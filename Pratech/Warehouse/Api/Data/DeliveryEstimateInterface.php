<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Warehouse\Api\Data;

interface DeliveryEstimateInterface
{
    public const WAREHOUSE_CODE = 'warehouse_code';
    public const DELIVERY_TIME = 'delivery_time';
    public const ESTIMATED_DATE = 'estimated_date';
    public const QUANTITY = 'quantity';

    /**
     * Get Warehouse Code.
     *
     * @return string
     */
    public function getWarehouseCode(): string;

    /**
     * Set Warehouse Code.
     *
     * @param string $warehouseCode
     * @return $this
     */
    public function setWarehouseCode(string $warehouseCode): self;

    /**
     * Get Delivery Time.
     *
     * @return int
     */
    public function getDeliveryTime(): int;

    /**
     * Set Delivery Time.
     *
     * @param int $deliveryTime
     * @return $this
     */
    public function setDeliveryTime(int $deliveryTime): self;

    /**
     * Get Estimated Date.
     *
     * @return string
     */
    public function getEstimatedDate(): string;

    /**
     * Set Estimated Date.
     *
     * @param string $estimatedDate
     * @return $this
     */
    public function setEstimatedDate(string $estimatedDate): self;

    /**
     * Get Quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set Quantity.
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): self;
}
