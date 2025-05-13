<?php
/**
 * Pratech_Wishlist
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Wishlist
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Wishlist\Api;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface WishlistManagementInterface
 * @api
 */
interface WishlistManagementInterface
{
    /**
     * Return Wishlist items.
     *
     * @param int $customerId
     * @param int|null $pincode
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getWishlistForCustomer(int $customerId, int $pincode = null): array;

    /**
     * Return Added wishlist item.
     *
     * @param int $customerId
     * @param int $productId
     * @return array
     * @throws \Exception
     */
    public function addItemToWishlist(int $customerId, int $productId): array;

    /**
     * Remove Item From Wishlist.
     *
     * @param int $customerId
     * @param int $wishlistItemId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function removeItemFromWishlist(int $customerId, int $wishlistItemId): array;
}
