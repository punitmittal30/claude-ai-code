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
use Pratech\Warehouse\Api\Data\WarehouseSlaInterface;

class WarehouseSla extends AbstractModel implements WarehouseSlaInterface
{
    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\WarehouseSla::class);
    }

    /**
     * @inheritDoc
     */
    public function getSlaId(): ?int
    {
        return $this->getData(self::SLA_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSlaId(int $slaId): static
    {
        return $this->setData(self::SLA_ID, $slaId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerPincode(): int
    {
        return $this->getData(self::CUSTOMER_PINCODE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerPincode(int $customerPincode): static
    {
        return $this->setData(self::CUSTOMER_PINCODE, $customerPincode);
    }

    /**
     * @inheritDoc
     */
    public function getWarehousePincode(): string
    {
        return $this->getData(self::WAREHOUSE_PINCODE);
    }

    /**
     * @inheritDoc
     */
    public function setWarehousePincode(string $warehousePincode): static
    {
        return $this->setData(self::WAREHOUSE_PINCODE, $warehousePincode);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryTime(): int
    {
        return $this->getData(self::DELIVERY_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryTime(int $deliveryTime): static
    {
        return $this->setData(self::DELIVERY_TIME, $deliveryTime);
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->getData(self::PRIORITY);
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $priority): static
    {
        return $this->setData(self::PRIORITY, $priority);
    }
}
