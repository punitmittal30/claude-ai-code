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

 namespace Pratech\Return\Model\TrackUpdates\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Return Track Updates Resource Model Class
 */
class ReturnTrackUpdates extends AbstractDb
{
    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_order_return_track_updates', 'entity_id');
    }
}
