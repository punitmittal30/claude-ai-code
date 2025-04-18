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
 * Customer Cart Interface to expose api related to customer cart.
 */
interface CustomerCartInterface
{
    /**
     * Create Customer Empty Cart
     *
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createCustomerEmptyCart(int $customerId): array;

    /**
     * Reset Customer Cart
     *
     * @param int $cartId
     * @return bool
     */
    public function resetCustomerCart(int $cartId): bool;

    /**
     * Returns information for the cart for a specified customer.
     *
     * @param int $customerId The customer ID.
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified customer does not exist.
     */
    public function getCustomerCart(int $customerId, int $pincode = null): array;

    /**
     * Add Product To Customer Cart
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     * @throws \Magento\Framework\Exception\LocalizedException The specified item or cart is not valid.
     */
    public function addItemToCustomerCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array;

    /**
     * Add Multiple Products To Customer Cart
     *
     * @param mixed $cartItems
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     * @throws \Magento\Framework\Exception\LocalizedException The specified item or cart is not valid.
     */
    public function addMultipleItemToCustomerCart(mixed $cartItems = []): array;

    /**
     * Update the specified cart item for customer.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem The item.
     * @return array Item.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateItemInCustomerCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array;

    /**
     * Removes the specified item from the specified cart for customer.
     *
     * @param int $cartId The cart ID.
     * @param int $itemId The item ID of the item to be removed.
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified item or cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The item could not be removed.
     */
    public function deleteItemFromCustomerCart(int $cartId, int $itemId): array;

    /**
     * Return quote totals data for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getCustomerCartTotals(string $cartId): \Magento\Quote\Api\Data\TotalsInterface;

    /**
     * Merge Guest Cart to Customer Cart.
     *
     * @param int $customerId
     * @param string $cartId
     * @return \Magento\Quote\Api\Data\CartInterface Cart object.
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function mergeCart(int $customerId, string $cartId): \Magento\Quote\Api\Data\CartInterface;

    /**
     * Get cross sell products of cart items
     *
     * @param string $type Possible values: customer|guest
     * @param string $cartId The cart ID.
     * @param int|null $pincode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCartCrossSellProducts(string $type, string $cartId, int $pincode = null): array;
}
