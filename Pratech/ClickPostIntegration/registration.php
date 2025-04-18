<?php
/**
 * Pratech_ClickPostIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ClickPostIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_ClickPostIntegration',
    __DIR__
);
