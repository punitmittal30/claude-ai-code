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
 * Coupon Management Interface to expose api related to customer coupon.
 */
interface CouponManagementInterface
{
    /**
     * Returns information for a coupon in a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getCustomerCoupon(int $cartId): array;

    /**
     * Adds a coupon by code to a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @param string $couponCode The coupon code data.
     * @param string|null $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be added.
     */
    public function setCustomerCoupon(int $cartId, string $couponCode, string $platform = null): array;

    /**
     * Deletes a coupon from a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotDeleteException The specified coupon could not be deleted.
     */
    public function removeCustomerCoupon(int $cartId): array;

    /**
     * OLD: Get Coupon Listing For Customer
     *
     * @param string|null $platform
     * @return array
     */
    public function getCustomerCouponListing(string $platform = null): array;

    /**
     * OLD: Get Hero Coupons for Both Guest and Customer
     *
     * @param string|null $platform
     * @return array
     */
    public function getHeroCoupons(string $platform = null): array;
}
