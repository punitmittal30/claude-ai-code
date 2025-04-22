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
use Pratech\Cart\Helper\CustomerCart as CustomerCartHelper;

/**
 * Customer Cart Management to empower customer cart api.
 */
class CustomerCartManagement implements \Pratech\Cart\Api\CustomerCartInterface
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
     * @param CustomerCartHelper $customerCartHelper
     * @param Response $response
     */
    public function __construct(
        private CustomerCartHelper $customerCartHelper,
        private Response           $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createCustomerEmptyCart(int $customerId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            [
                "quote_id" => $this->customerCartHelper->createCustomerEmptyCart($customerId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function resetCustomerCart(int $cartId): bool
    {
        return $this->customerCartHelper->resetCustomerCart($cartId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerCart(int $customerId, int $pincode = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            $this->customerCartHelper->getCustomerCart($customerId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function addItemToCustomerCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Product added to cart.',
            self::CART_API_RESOURCE,
            $this->customerCartHelper->addItemToCustomerCart($cartItem)
        );
    }

    /**
     * @inheritDoc
     */
    public function addMultipleItemToCustomerCart(mixed $cartItems = []): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Multiple products added to cart',
            self::CART_API_RESOURCE,
            $this->customerCartHelper->addMultipleItemToCustomerCart($cartItems)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerCartTotals(string $cartId): \Magento\Quote\Api\Data\TotalsInterface
    {
        return $this->customerCartHelper->getCustomerCartTotals($cartId);
    }

    /**
     * @inheritDoc
     */
    public function mergeCart(int $customerId, string $cartId): \Magento\Quote\Api\Data\CartInterface
    {
        return $this->customerCartHelper->mergeCart($customerId, $cartId);
    }

    /**
     * @inheritDoc
     */
    public function updateItemInCustomerCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Product quantity updated',
            self::CART_API_RESOURCE,
            $this->customerCartHelper->updateItemInCustomerCart($cartItem)
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteItemFromCustomerCart(int $cartId, int $itemId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'Item removed from the cart',
            self::CART_API_RESOURCE,
            [
                'is_deleted' => $this->customerCartHelper->deleteItemFromCustomerCart($cartId, $itemId)
            ]
        );
    }
}
