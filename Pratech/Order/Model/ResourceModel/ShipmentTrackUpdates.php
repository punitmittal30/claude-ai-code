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

namespace Pratech\Order\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Shipment Track Updates Resource Model Class
 */
class ShipmentTrackUpdates extends AbstractDb
{
    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_shipment_track_updates', 'entity_id');
    }
}
