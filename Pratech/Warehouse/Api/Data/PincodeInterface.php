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

interface PincodeInterface
{
    public const ENTITY_ID = 'entity_id';
    public const PINCODE = 'pincode';
    public const IS_SERVICEABLE = 'is_serviceable';
    public const CITY = 'city';
    public const STATE = 'state';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get ID.
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set ID.
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): static;

    /**
     * Get Pincode.
     *
     * @return int
     */
    public function getPincode(): int;

    /**
     * Set Pincode.
     *
     * @param int $pincode
     * @return $this
     */
    public function setPincode(int $pincode): static;

    /**
     * Is Serviceable?
     *
     * @return bool
     */
    public function getIsServiceable(): bool;

    /**
     * Set Is Serviceable.
     *
     * @param bool $isServiceable
     * @return $this
     */
    public function setIsServiceable(bool $isServiceable): static;

    /**
     * Get City.
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * Set City.
     *
     * @param string|null $city
     * @return $this
     */
    public function setCity(?string $city): static;

    /**
     * Get State.
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Set state.
     *
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state): static;
}
