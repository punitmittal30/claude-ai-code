<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Model\ResourceModel\ShipmentStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Order\Model\ShipmentStatus;
use Pratech\Order\Model\ResourceModel\ShipmentStatus as ShipmentStatusResourceModel;

/**
 * Shipment Status collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'status_id';

    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ShipmentStatus::class, ShipmentStatusResourceModel::class);
    }
}
