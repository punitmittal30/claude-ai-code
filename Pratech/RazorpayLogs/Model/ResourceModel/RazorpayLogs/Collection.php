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

namespace Pratech\RazorpayLogs\Model\ResourceModel\RazorpayLogs;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Constructor to map model and resource model.
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\RazorpayLogs\Model\RazorpayLogs::class,
            \Pratech\RazorpayLogs\Model\ResourceModel\RazorpayLogs::class
        );
    }
}
