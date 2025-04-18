<?php
/**
 * Pratech_CatalogImportExport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CatalogImportExport
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pratech_CatalogImportExport',
    __DIR__
);
