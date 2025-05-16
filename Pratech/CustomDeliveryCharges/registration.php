<?php
/**
 * Pratech_CustomDeliveryCharges
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CustomDeliveryCharges
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_CustomDeliveryCharges',
    __DIR__
);
