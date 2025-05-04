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

namespace Pratech\RazorpayLogs\Model;

use Magento\Framework\Model\AbstractModel;

class RazorpayLogs extends AbstractModel
{
    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\RazorpayLogs::class);
    }
}
