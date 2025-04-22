<?php
/**
 * Hyuga_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Cart\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface CartManagementInterface
{

    /**
     * Get cross-sell products of cart items.
     *
     * @param string $type Possible values: customer|guest
     * @param string $cartId The cart ID.
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCartCrossSellProducts(string $type, string $cartId, int $pincode = null): array;
}
