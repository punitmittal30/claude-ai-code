<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Api;

/**
 * System Config Interface to fetch system config values.
 */
interface SystemConfigInterface
{
    /**
     * Retrieve Otp Bypass Numbers.
     *
     * @return array
     */
    public function getOtpByPassNumbers(): array;

    /**
     * Retrieve Footer Quick Links.
     *
     * @return array
     */
    public function getFooterQuickLinks(): array;
}
