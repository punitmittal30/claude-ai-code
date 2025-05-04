<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\Reason;

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\ReasonInterface;

class Reason extends AbstractModel implements ReasonInterface
{
    /**
     * Construct.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Reason::class);
        $this->setIdFieldName(ReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(ReasonInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(ReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(ReasonInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(ReasonInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(ReasonInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(ReasonInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPosition($position)
    {
        return $this->setData(ReasonInterface::POSITION, (int)$position);
    }

    /**
     * @inheritdoc
     */
    public function getPosition()
    {
        return (int)$this->_getData(ReasonInterface::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(ReasonInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(ReasonInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(ReasonInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(ReasonInterface::IS_DELETED);
    }
}
