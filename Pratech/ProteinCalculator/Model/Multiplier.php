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
use Pratech\ProteinCalculator\Api\Data\MultiplierInterface;

class Multiplier extends AbstractModel implements MultiplierInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\Pratech\ProteinCalculator\Model\ResourceModel\Multiplier::class);
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
    public function getGender()
    {
        return $this->getData(self::GENDER);
    }

    /**
     * @inheritDoc
     */
    public function setGender($gender)
    {
        return $this->setData(self::GENDER, $gender);
    }

    /**
     * @inheritDoc
     */
    public function getBodyType()
    {
        return $this->getData(self::BODY_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setBodyType($bodyType)
    {
        return $this->setData(self::BODY_TYPE, $bodyType);
    }

    /**
     * @inheritDoc
     */
    public function getGoal()
    {
        return $this->getData(self::GOAL);
    }

    /**
     * @inheritDoc
     */
    public function setGoal($goal)
    {
        return $this->setData(self::GOAL, $goal);
    }

    /**
     * @inheritDoc
     */
    public function getMultiplier()
    {
        return $this->getData(self::MULTIPLIER);
    }

    /**
     * @inheritDoc
     */
    public function setMultiplier($multiplier)
    {
        return $this->setData(self::MULTIPLIER, $multiplier);
    }
}
