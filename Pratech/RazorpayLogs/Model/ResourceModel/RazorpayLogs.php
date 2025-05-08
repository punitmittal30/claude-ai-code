<?php
/**
 * Pratech_RazorpayLogs
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RazorpayLogs
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RazorpayLogs\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RazorpayLogs extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('razorpay_logs', 'entity_id');
    }
}
