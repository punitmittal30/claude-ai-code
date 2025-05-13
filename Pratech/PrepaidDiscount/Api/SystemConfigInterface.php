<?php
/**
 * Pratech_PrepaidDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\PrepaidDiscount
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\PrepaidDiscount\Api;

/**
 * System Config Interface to fetch system config values.
 */
interface SystemConfigInterface
{
    /**
     * Retrieve Prepaid Discount Slabs.
     *
     * @return array
     */
    public function getPrepaidDiscountInfo(): array;
}
