<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface WondersoftApiInterface
{
    /**
     * Get API token
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * Push product to Wondersoft
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function pushProduct(ProductInterface $product): bool;

    /**
     * Push price list to Wondersoft
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function pushPriceList(ProductInterface $product): bool;
}
