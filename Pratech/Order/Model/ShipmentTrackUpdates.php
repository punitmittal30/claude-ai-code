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

namespace Pratech\Order\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Shipment Track Updates Model Class
 */
class ShipmentTrackUpdates extends AbstractModel
{
    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\ShipmentTrackUpdates::class);
    }
}
