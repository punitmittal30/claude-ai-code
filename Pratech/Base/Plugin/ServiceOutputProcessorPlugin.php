<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Plugin;

use Magento\Framework\Webapi\ServiceOutputProcessor;

class ServiceOutputProcessorPlugin
{
    /**
     * Plugin to convert output value
     *
     * @param ServiceOutputProcessor $subject
     * @param callable $proceed
     * @param $data
     * @param string $type
     * @return mixed
     */
    public function aroundConvertValue(ServiceOutputProcessor $subject, callable $proceed, $data, string $type)
    {
        if ($type == 'array') {
            return $data;
        }
        return $proceed($data, $type);
    }
}
