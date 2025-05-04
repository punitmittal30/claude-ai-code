<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_SqsIntegration',
    __DIR__
);
