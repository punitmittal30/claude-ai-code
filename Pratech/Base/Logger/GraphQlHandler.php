<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Logger;

use Monolog\Logger;

class GraphQlHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/graphql.log';
}
