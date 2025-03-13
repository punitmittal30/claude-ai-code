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

interface WarehouseInterface
{
    public const WAREHOUSE_ID = 'warehouse_id';
    public const WAREHOUSE_CODE = 'warehouse_code';
    public const NAME = 'name';
    public const PINCODE = 'pincode';
    public const ADDRESS = 'address';
    public const IS_ACTIVE = 'is_active';
    public const IS_DARK_STORE = 'is_dark_store';
    public const WAREHOUSE_URL = 'warehouse_url';

    /**
     * Get Warehouse ID.
     *
     * @return int
     */
    public function getWarehouseId();

    /**
     * Set Warehouse ID.
     *
     * @param int $warehouseId
     * @return $this
     */
    public function setWarehouseId(int $warehouseId);

    /**
     * Get Warehouse Code.
     *
     * @return string
     */
    public function getWarehouseCode();

    /**
     * Get Warehouse Url.
     *
     * @return string
     */
    public function getWarehouseUrl();

    /**
     * Set Warehouse Code.
     *
     * @param string $warehouseCode
     * @return $this
     */
    public function setWarehouseCode(string $warehouseCode);

    /**
     * Set Warehouse Url.
     *
     * @param string $warehouseUrl
     * @return $this
     */
    public function setWarehouseUrl(string $warehouseUrl);

    /**
     * Get Warehouse Name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set Warehouse Name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * Get Warehouse Pincode.
     *
     * @return int
     */
    public function getPincode();

    /**
     * Set Warehouse Pincode.
     *
     * @param int $pincode
     * @return $this
     */
    public function setPincode(int $pincode);

    /**
     * Get Warehouse Address.
     *
     * @return string
     */
    public function getAddress();

    /**
     * Set Warehouse Address.
     *
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address);

    /**
     * Is Warehouse Active.
     *
     * @return bool
     */
    public function getIsActive();

    /**
     * Set Warehouse Active.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive);

    /**
     * Is Dark Store.
     *
     * @return bool
     */
    public function getIsDarkStore();

    /**
     * Set Is Dark Store.
     *
     * @param bool $isDarkStore
     * @return $this
     */
    public function setIsDarkStore(bool $isDarkStore);
}
