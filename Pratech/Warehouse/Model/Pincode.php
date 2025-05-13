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

namespace Pratech\Warehouse\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\Warehouse\Api\Data\PincodeInterface;

class Pincode extends AbstractModel implements PincodeInterface
{
    /**
     * Construct.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Pincode::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) ? (int)$this->getData(self::ENTITY_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId): static
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getPincode(): int
    {
        return (int)$this->getData(self::PINCODE);
    }

    /**
     * @inheritDoc
     */
    public function setPincode(int $pincode): static
    {
        return $this->setData(self::PINCODE, $pincode);
    }

    /**
     * @inheritDoc
     */
    public function getIsServiceable(): bool
    {
        return (bool)$this->getData(self::IS_SERVICEABLE);
    }

    /**
     * @inheritDoc
     */
    public function setIsServiceable(bool $isServiceable): static
    {
        return $this->setData(self::IS_SERVICEABLE, $isServiceable);
    }

    /**
     * @inheritDoc
     */
    public function getCity(): ?string
    {
        return $this->getData(self::CITY);
    }

    /**
     * @inheritDoc
     */
    public function setCity(?string $city): static
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * @inheritDoc
     */
    public function getState(): ?string
    {
        return $this->getData(self::STATE);
    }

    /**
     * @inheritDoc
     */
    public function setState(?string $state): static
    {
        return $this->setData(self::STATE, $state);
    }
}
