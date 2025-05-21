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
use Pratech\Warehouse\Api\Data\WarehouseInventoryInterface;

class WarehouseInventory extends AbstractModel implements WarehouseInventoryInterface
{
    /**
     * @inheritDoc
     */
    public function getWarehouseCode(): string
    {
        return $this->getData(self::WAREHOUSE_CODE,);
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseCode(string $warehouseCode): static
    {
        return $this->setData(self::WAREHOUSE_CODE, $warehouseCode);
    }

    /**
     * @inheritDoc
     */
    public function getSku(): string
    {
        return $this->getData(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku(string $sku): static
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): int
    {
        return $this->getData(self::QUANTITY);
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(int $quantity): static
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\WarehouseInventory::class);
    }

    /**
     * @inheritDoc
     */
    public function getInventoryId(): ?int
    {
        return $this->getData(self::INVENTORY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInventoryId(int $inventoryId): static
    {
        return $this->setData(self::INVENTORY_ID, $inventoryId);
    }
}
