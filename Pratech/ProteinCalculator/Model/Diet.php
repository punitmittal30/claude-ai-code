<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ProteinCalculator\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\ProteinCalculator\Api\Data\DietInterface;

class Diet extends AbstractModel implements DietInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\Pratech\ProteinCalculator\Model\ResourceModel\Diet::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getDietType()
    {
        return $this->getData(self::DIET_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setDietType($dietType)
    {
        return $this->setData(self::DIET_TYPE, $dietType);
    }

    /**
     * @inheritDoc
     */
    public function getDiet()
    {
        return json_decode($this->getData(self::DIET), true);
    }

    /**
     * @inheritDoc
     */
    public function setDiet($diet)
    {
        return $this->setData(self::DIET, json_encode($diet));
    }

    /**
     * @inheritDoc
     */
    public function getBudget()
    {
        return $this->getData(self::BUDGET);
    }

    /**
     * @inheritDoc
     */
    public function setBudget($budget)
    {
        return $this->setData(self::BUDGET, $budget);
    }

    /**
     * @inheritDoc
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }
}
