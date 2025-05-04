<?php
/**
 * Pratech_SlackNotifier
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SlackNotifier
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_SlackNotifier',
    __DIR__
);
