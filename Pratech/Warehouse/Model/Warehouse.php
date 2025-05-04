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

namespace Pratech\Warehouse\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\Warehouse\Api\Data\WarehouseInterface;

class Warehouse extends AbstractModel implements WarehouseInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'warehouse';

    /**
     * Get Warehouse ID.
     *
     * @return int
     */
    public function getWarehouseId()
    {
        return $this->getData(self::WAREHOUSE_ID);
    }

    /**
     * Set Warehouse ID.
     *
     * @param int $warehouseId
     * @return Warehouse
     */
    public function setWarehouseId(int $warehouseId)
    {
        return $this->setData(self::WAREHOUSE_ID, $warehouseId);
    }

    /**
     * Get Warehouse Code.
     *
     * @return string
     */
    public function getWarehouseCode()
    {
        return $this->getData(self::WAREHOUSE_CODE);
    }

    /**
     * Set Warehouse Code.
     *
     * @param string $warehouseCode
     * @return Warehouse
     */
    public function setWarehouseCode(string $warehouseCode)
    {
        return $this->setData(self::WAREHOUSE_CODE, $warehouseCode);
    }

    /**
     * Get Warehouse Name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Warehouse Name.
     *
     * @param string $name
     * @return Warehouse
     */
    public function setName(string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Pincode.
     *
     * @return int
     */
    public function getPincode()
    {
        return $this->getData(self::PINCODE);
    }

    /**
     * Set Pincode.
     *
     * @param int $pincode
     * @return Warehouse
     */
    public function setPincode(int $pincode)
    {
        return $this->setData(self::PINCODE, $pincode);
    }

    /**
     * Get Warehouse Address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->getData(self::ADDRESS);
    }

    /**
     * Set Warehouse Address.
     *
     * @param string $address
     * @return Warehouse
     */
    public function setAddress(string $address)
    {
        return $this->setData(self::ADDRESS, $address);
    }

    /**
     * Is Warehouse Active.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * Set Is Warehouse Active.
     *
     * @param bool $isActive
     * @return Warehouse
     */
    public function setIsActive(bool $isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseUrl()
    {
        return $this->getData(self::WAREHOUSE_URL);
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseUrl(string $warehouseUrl)
    {
        return $this->setData(self::WAREHOUSE_URL, $warehouseUrl);
    }

    /**
     * @inheritDoc
     */
    public function getIsDarkStore()
    {
        return $this->getData(self::IS_DARK_STORE);
    }

    /**
     * @inheritDoc
     */
    public function setIsDarkStore(bool $isDarkStore)
    {
        return $this->setData(self::IS_DARK_STORE, $isDarkStore);
    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Warehouse::class);
    }
}
