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

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Warehouse\Api\Data\DeliveryEstimateInterface;

class DeliveryEstimate extends DataObject implements DeliveryEstimateInterface
{
    /**
     * @inheritDoc
     */
    public function getWarehouseCode(): string
    {
        return $this->getData(self::WAREHOUSE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseCode(string $warehouseCode): DeliveryEstimateInterface
    {
        return $this->setData(self::WAREHOUSE_CODE, $warehouseCode);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryTime(): int
    {
        return (int)$this->getData(self::DELIVERY_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryTime(int $deliveryTime): DeliveryEstimateInterface
    {
        return $this->setData(self::DELIVERY_TIME, $deliveryTime);
    }

    /**
     * @inheritDoc
     */
    public function getEstimatedDate(): string
    {
        return $this->getData(self::ESTIMATED_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setEstimatedDate(string $estimatedDate): DeliveryEstimateInterface
    {
        return $this->setData(self::ESTIMATED_DATE, $estimatedDate);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): int
    {
        return (int)$this->getData(self::QUANTITY);
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(int $quantity): DeliveryEstimateInterface
    {
        return $this->setData(self::QUANTITY, $quantity);
    }
}
