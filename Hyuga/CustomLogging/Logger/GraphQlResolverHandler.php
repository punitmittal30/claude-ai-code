<?php
/**
 * Hyuga_CustomLogging
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CustomLogging
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CustomLogging\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class GraphQlResolverHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/graphql_resolver.log';
}
