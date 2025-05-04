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

namespace Pratech\Return\Model\Order;

use Magento\Framework\DataObject;
use Pratech\Return\Api\Data\ReturnOrderInterface;

class ReturnOrder extends DataObject implements ReturnOrderInterface
{

    /**
     * @inheritdoc
     */
    public function setOrder($order)
    {
        return $this->setData(ReturnOrderInterface::ORDER, $order);
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return $this->_getData(ReturnOrderInterface::ORDER);
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        return $this->_getData(ReturnOrderInterface::ITEMS);
    }

    /**
     * @inheritdoc
     */
    public function setItems($items)
    {
        return $this->setData(ReturnOrderInterface::ITEMS, $items);
    }
}
