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

namespace Pratech\HealthCheck\Api;

interface HealthCheckInterface
{
    /**
     * Get Health Check Status
     *
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getStatus(): string;
}
