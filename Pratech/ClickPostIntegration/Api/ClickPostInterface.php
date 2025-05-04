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
namespace Pratech\ClickPostIntegration\Api;

interface ClickPostInterface
{
    /**
     * Get Estimated Delivery Date for a particular sku
     *
     * @param string $destination
     * @return array
     */
    public function getEstimatedDeliveryDate(string $destination): array;
}
