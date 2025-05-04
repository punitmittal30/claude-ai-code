<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Warehouse\Api;

interface DeliveryEstimateRepositoryInterface
{
    /**
     * Get delivery estimate for a single product
     *
     * @param string $sku
     * @param int $pincode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEstimate(string $sku, int $pincode): array;
}
