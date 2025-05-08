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

namespace Pratech\Order\Model\ResourceModel\ShipmentTrackUpdates;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Order\Model\ShipmentTrackUpdates;
use Pratech\Order\Model\ResourceModel\ShipmentTrackUpdates as TrackUpdatesResourceModel;

/**
 * Shipment Track Updates collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ShipmentTrackUpdates::class, TrackUpdatesResourceModel::class);
    }
}
