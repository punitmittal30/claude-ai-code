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

 namespace Pratech\Return\Model\TrackUpdates;

use Magento\Framework\Model\AbstractModel;

/**
 * Return Track Updates Model Class
 */
class ReturnTrackUpdates extends AbstractModel
{
    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\ReturnTrackUpdates::class);
    }
}
