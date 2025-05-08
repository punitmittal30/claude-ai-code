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

namespace Pratech\Warehouse\Api\Data;

interface WarehouseSlaInterface
{
    public const SLA_ID = 'sla_id';
    public const CUSTOMER_PINCODE = 'customer_pincode';
    public const WAREHOUSE_PINCODE = 'warehouse_pincode';
    public const DELIVERY_TIME = 'delivery_time';

    public const PRIORITY = 'priority';

    /**
     * Get SLA ID
     *
     * @return int|null
     */
    public function getSlaId(): ?int;

    /**
     * Set SLA ID
     *
     * @param int $slaId
     * @return $this
     */
    public function setSlaId(int $slaId): static;

    /**
     * Get customer pincode
     *
     * @return int
     */
    public function getCustomerPincode(): int;

    /**
     * Set customer pincode
     *
     * @param int $customerPincode
     * @return $this
     */
    public function setCustomerPincode(int $customerPincode): static;

    /**
     * Get warehouse pincode
     *
     * @return string
     */
    public function getWarehousePincode(): string;

    /**
     * Set warehouse pincode
     *
     * @param string $warehousePincode
     * @return $this
     */
    public function setWarehousePincode(string $warehousePincode): static;

    /**
     * Get delivery time
     *
     * @return int
     */
    public function getDeliveryTime(): int;

    /**
     * Set delivery time
     *
     * @param int $deliveryTime
     * @return $this
     */
    public function setDeliveryTime(int $deliveryTime): static;

    /**
     * Get priority
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Set priority
     *
     * @param int $priority
     * @return $this
     */
    public function setPriority(int $priority): static;
}
