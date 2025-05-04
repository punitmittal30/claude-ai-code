<?php
/**
 * Hyuga_LogManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\LogManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\LogManagement\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class CartApiHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/cart_api.log';
}
