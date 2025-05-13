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
 * Guest Cart Interface to expose api related to guest cart.
 */
interface GuestCartInterface
{
    /**
     * Create Guest Cart
     *
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException The empty cart and quote could not be created.
     */
    public function createEmptyCart(): array;

    /**
     * Create Guest Cart
     *
     * @param string $cartId
     * @return bool
     */
    public function resetGuestCart(string $cartId): bool;

    /**
     * Get Guest Cart Details
     *
     * @param string $cartId
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getGuestCart(string $cartId, int $pincode = null): array;

    /**
     * Add Product To Guest Cart
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     */
    public function addItemToGuestCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array;

    /**
     * Add Product To Guest Cart
     *
     * @param mixed $cartItems
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     */
    public function addMultipleItemToGuestCart(mixed $cartItems): array;

    /**
     * Update the specified cart item for guest.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem The item.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     */
    public function updateItemInGuestCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array;

    /**
     * Remove the specified item from the specified cart for guest.
     *
     * @param string $cartId The cart ID.
     * @param int $itemId The item ID of the item to be removed.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified item or cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The item could not be removed.
     */
    public function deleteItemFromGuestCart(string $cartId, int $itemId): array;

    /**
     * Return quote totals data for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getGuestCartTotals(string $cartId): \Magento\Quote\Api\Data\TotalsInterface;
}
