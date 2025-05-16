<?php
/**
 * Pratech_BagDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\BagDiscount
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_BagDiscount',
    __DIR__
);
