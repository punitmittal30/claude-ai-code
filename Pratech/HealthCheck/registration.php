<?php
/**
 * Pratech_HealthCheck
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\HealthCheck
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_HealthCheck',
    __DIR__
);
