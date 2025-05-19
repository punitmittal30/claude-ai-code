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

 namespace Pratech\Return\Model\TrackUpdates\ResourceModel\ReturnTrackUpdates;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Return\Model\TrackUpdates\ReturnTrackUpdates;
use Pratech\Return\Model\TrackUpdates\ResourceModel\ReturnTrackUpdates as TrackUpdatesResourceModel;

/**
 * Return Track Updates collection class
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
        $this->_init(ReturnTrackUpdates::class, TrackUpdatesResourceModel::class);
    }
}
