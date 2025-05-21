<?php

namespace Pratech\Warehouse\Model\Data;

use Pratech\Warehouse\Api\Data\InventoryListInterface;
use Magento\Framework\DataObject;

class InventoryList extends DataObject implements InventoryListInterface
{
    /**
     * @inheritDoc
     */
    public function getSkucode()
    {
        return $this->getData('skucode');
    }

    /**
     * @inheritDoc
     */
    public function getLocation()
    {
        return $this->getData('location');
    }

    /**
     * @inheritDoc
     */
    public function getQty()
    {
        return $this->getData('qty');
    }

    /**
     * @inheritDoc
     */
    public function getLot()
    {
        return $this->getData('lot');
    }

    /**
     * @inheritDoc
     */
    public function getLottable01()
    {
        return $this->getData('lottable_01');
    }

    /**
     * @inheritDoc
     */
    public function getLottable03()
    {
        return $this->getData('lottable_03');
    }

    /**
     * @inheritDoc
     */
    public function getLottable06()
    {
        return $this->getData('lottable_06');
    }

    /**
     * @inheritDoc
     */
    public function getClientId()
    {
        return $this->getData('clientId');
    }

    /**
     * @inheritDoc
     */
    public function getOrgId()
    {
        return $this->getData('OrgId');
    }

    /**
     * @inheritDoc
     */
    public function setSkucode($skucode)
    {
        return $this->setData('skucode', $skucode);
    }

    /**
     * @inheritDoc
     */
    public function setLocation($location)
    {
        return $this->setData('location', $location);
    }

    /**
     * @inheritDoc
     */
    public function setQty($qty)
    {
        return $this->setData('qty', $qty);
    }

    /**
     * @inheritDoc
     */
    public function setLot($lot)
    {
        return $this->setData('lot', $lot);
    }

    /**
     * @inheritDoc
     */
    public function setLottable01($lottable01)
    {
        return $this->setData('lottable_01', $lottable01);
    }

    /**
     * @inheritDoc
     */
    public function setLottable03($lottable03)
    {
        return $this->setData('lottable_03', $lottable03);
    }

    /**
     * @inheritDoc
     */
    public function setLottable06($lottable06)
    {
        return $this->setData('lottable_06', $lottable06);
    }

    /**
     * @inheritDoc
     */
    public function setClientId($clientId)
    {
        return $this->setData('clientId', $clientId);
    }

    /**
     * @inheritDoc
     */
    public function setOrgId($orgId)
    {
        return $this->setData('OrgId', $orgId);
    }
}
