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

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Hyuga_CustomLogging',
    __DIR__
);
