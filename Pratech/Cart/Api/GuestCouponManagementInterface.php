<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Api;

/**
 * Guest Coupon Management Interface to expose api related to guest coupon.
 */
interface GuestCouponManagementInterface
{
    /**
     * Return information for a coupon in a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @return array The coupon code data.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getGuestCoupon(string $cartId): array;

    /**
     * Add a coupon by code to a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @param string $couponCode The coupon code data.
     * @param string|null $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be added.
     */
    public function setGuestCoupon(string $cartId, string $couponCode, string $platform = null): array;

    /**
     * Delete a coupon from a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotDeleteException The specified coupon could not be deleted.
     */
    public function removeGuestCoupon(string $cartId): array;

    /**
     * Get Coupon Listing For Guest Customer
     *
     * @param string|null $platform
     * @return array
     */
    public function getGuestCouponListing(string $platform = null): array;
}
