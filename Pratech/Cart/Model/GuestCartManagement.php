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

namespace Pratech\Cart\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Cart\Helper\GuestCart as GuestCartHelper;

/**
 * Guest Cart Management to empower guest cart api.
 */
class GuestCartManagement implements \Pratech\Cart\Api\GuestCartInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * CART API RESOURCE
     */
    private const CART_API_RESOURCE = 'cart';

    /**
     * Quote Management Constructor
     *
     * @param GuestCartHelper $guestCartHelper
     * @param Response $response
     */
    public function __construct(
        private GuestCartHelper $guestCartHelper,
        private Response        $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createEmptyCart(): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            [
                "quote_id" => $this->guestCartHelper->createGuestCart()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function resetGuestCart(string $cartId): bool
    {
        return $this->guestCartHelper->resetGuestCart($cartId);
    }

    /**
     * @inheritDoc
     */
    public function getGuestCart(string $cartId, int $pincode = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            $this->guestCartHelper->getGuestCart($cartId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function addItemToGuestCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Product added to cart.',
            self::CART_API_RESOURCE,
            $this->guestCartHelper->addItemToGuestCart($cartItem)
        );
    }

    /**
     * @inheritDoc
     */
    public function addMultipleItemToGuestCart(mixed $cartItems = []): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Products added to cart',
            self::CART_API_RESOURCE,
            $this->guestCartHelper->addMultipleItemToGuestCart($cartItems)
        );
    }

    /**
     * @inheritDoc
     */
    public function updateItemInGuestCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Product quantity updated',
            self::CART_API_RESOURCE,
            $this->guestCartHelper->updateItemInGuestCart($cartItem)
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteItemFromGuestCart(string $cartId, int $itemId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Item removed from the cart',
            self::CART_API_RESOURCE,
            [
                'is_deleted' => $this->guestCartHelper->deleteItemFromGuestCart($cartId, $itemId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuestCartTotals(string $cartId): \Magento\Quote\Api\Data\TotalsInterface
    {
        return $this->guestCartHelper->getGuestCartTotals($cartId);
    }
}
