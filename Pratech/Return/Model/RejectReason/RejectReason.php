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

namespace Pratech\Return\Model\RejectReason;

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\RejectReasonInterface;

class RejectReason extends AbstractModel implements RejectReasonInterface
{
    /**
     * Construct.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\RejectReason::class);
        $this->setIdFieldName(RejectReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(RejectReasonInterface::REASON_ID, (int)$reasonId);
    }

    /**
     * @inheritdoc
     */
    public function getReasonId()
    {
        return (int)$this->_getData(RejectReasonInterface::REASON_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(RejectReasonInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(RejectReasonInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(RejectReasonInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(RejectReasonInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPosition($position)
    {
        return $this->setData(RejectReasonInterface::POSITION, (int)$position);
    }

    /**
     * @inheritdoc
     */
    public function getPosition()
    {
        return (int)$this->_getData(RejectReasonInterface::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(RejectReasonInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(RejectReasonInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(RejectReasonInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(RejectReasonInterface::IS_DELETED);
    }
}
