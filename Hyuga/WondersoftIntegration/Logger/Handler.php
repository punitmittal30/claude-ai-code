<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/wondersoft.log';
}
