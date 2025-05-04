<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Api;

/**
 * Coupon Management Interface to expose api related to customer coupon.
 */
interface CouponManagementInterface
{
    /**
     * NEW: Get Coupon Listing For Customer
     *
     * @param int $quoteId
     * @param string $platform
     * @return array
     */
    public function getCouponListingForCustomer(int $quoteId, string $platform): array;

    /**
     * NEW: Get Hero Coupons For Customer
     *
     * @param int $quoteId
     * @param string $platform
     * @return array
     */
    public function getHeroCouponForCustomer(int $quoteId, string $platform): array;

    /**
     * Returns information of multiple applied coupon in a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getCustomerAppliedCoupons(int $cartId): array;

    /**
     * Adds a coupon by code to a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @param string $couponCode The coupon code data.
     * @param string $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be added.
     */
    public function applyCustomerCoupons(int $cartId, string $couponCode, string $platform): array;

    /**
     * Remove a coupon from a specified customer cart.
     *
     * @param int $cartId The cart ID.
     * @param string $couponCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be saved.
     * @throws \Magento\Framework\Exception\CouldNotDeleteException The specified coupon could not be deleted.
     */
    public function removeCustomerCoupons(int $cartId, string $couponCode): array;

    /**
     * NEW: Get Coupon Listing For Guest
     *
     * @param string $quoteId
     * @param string $platform
     * @return array
     */
    public function getCouponListingForGuest(string $quoteId, string $platform): array;

    /**
     * NEW: Get Hero Coupons For Guest
     *
     * @param string $quoteId
     * @param string $platform
     * @return array
     */
    public function getHeroCouponForGuest(string $quoteId, string $platform): array;

    /**
     * Return applied coupon information for a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @return array The coupon code data.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getGuestAppliedCoupons(string $cartId): array;

    /**
     * Apply multiple coupons by code to a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @param string $couponCode The coupon code data.
     * @param string $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be added.
     */
    public function applyGuestCoupons(string $cartId, string $couponCode, string $platform): array;

    /**
     * Remove a coupon from a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @param string $couponCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified coupon could not be saved.
     * @throws \Magento\Framework\Exception\CouldNotDeleteException The specified coupon could not be deleted.
     */
    public function removeGuestCoupons(string $cartId, string $couponCode): array;

    /**
     * Get Coupons by ruleId
     *
     * @param int $ruleId
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function getCouponsByRuleId(int $ruleId, int $pageSize, int $currentPage): array;
}
