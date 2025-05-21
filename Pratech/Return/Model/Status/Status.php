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

namespace Pratech\Return\Model\Status;

use Magento\Framework\Model\AbstractModel;
use Pratech\Return\Api\Data\StatusInterface;

class Status extends AbstractModel implements StatusInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Status::class);
        $this->setIdFieldName(StatusInterface::STATUS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStatusId($statusId)
    {
        return $this->setData(StatusInterface::STATUS_ID, (int)$statusId);
    }

    /**
     * @inheritdoc
     */
    public function getStatusId()
    {
        return (int)$this->_getData(StatusInterface::STATUS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIsEnabled($isEnabled)
    {
        return $this->setData(StatusInterface::IS_ENABLED, (bool)$isEnabled);
    }

    /**
     * @inheritdoc
     */
    public function isEnabled()
    {
        return (bool)$this->_getData(StatusInterface::IS_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setIsInitial($isInitial)
    {
        return $this->setData(StatusInterface::IS_INITIAL, (bool)$isInitial);
    }

    /**
     * @inheritdoc
     */
    public function isInitial()
    {
        return (bool)$this->_getData(StatusInterface::IS_INITIAL);
    }

    /**
     * @inheritDoc
     */
    public function setAutoEvent($autoEvent)
    {
        return $this->setData(StatusInterface::AUTO_EVENT, (int)$autoEvent);
    }

    /**
     * @inheritDoc
     */
    public function getAutoEvent()
    {
        return (int)$this->_getData(StatusInterface::AUTO_EVENT);
    }

    /**
     * @inheritdoc
     */
    public function setState($state)
    {
        return $this->setData(StatusInterface::STATE, (int)$state);
    }

    /**
     * @inheritdoc
     */
    public function getState()
    {
        return (int)$this->_getData(StatusInterface::STATE);
    }

    /**
     * @inheritdoc
     */
    public function setGrid($grid)
    {
        return $this->setData(StatusInterface::GRID, (int)$grid);
    }

    /**
     * @inheritdoc
     */
    public function getGrid()
    {
        return (int)$this->_getData(StatusInterface::GRID);
    }

    /**
     * @inheritDoc
     */
    public function setPriority($priority)
    {
        return $this->setData(StatusInterface::PRIORITY, (int)$priority);
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return (int)$this->_getData(StatusInterface::PRIORITY);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        return $this->setData(StatusInterface::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->_getData(StatusInterface::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(StatusInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(StatusInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setIsDeleted($isDeleted)
    {
        return $this->setData(StatusInterface::IS_DELETED, $isDeleted);
    }

    /**
     * @inheritdoc
     */
    public function getIsDeleted()
    {
        return $this->_getData(StatusInterface::IS_DELETED);
    }
}
