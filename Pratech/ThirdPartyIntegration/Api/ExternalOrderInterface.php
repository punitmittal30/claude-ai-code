<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Api;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ExternalOrderInterface
{
    /**
     * Create Guest Cart
     *
     * @param string $platform
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException The empty cart and quote could not be created.
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function createEmptyCart(string $platform): array;

    /**
     * Add Product To Guest Cart
     *
     * @param string $platform
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function addItemToGuestCart(string $platform, \Magento\Quote\Api\Data\CartItemInterface $cartItem): array;

    /**
     * Save Address Information.
     *
     * @param string $platform
     * @param string $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function saveAddressInformation(
        string                                                  $platform,
        string                                                  $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ): \Magento\Checkout\Api\Data\PaymentDetailsInterface;

    /**
     * Get info about product by product id
     *
     * @param string $platform
     * @param int $productId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getProductById(string $platform, int $productId): array;

    /**
     * Get Products By Category ID
     *
     * @param int $categoryId
     * @param string $platform
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws AuthorizationException
     */
    public function getProductsByCategoryId(
        int    $categoryId,
        string $platform,
        int    $pageSize,
        int    $currentPage
    ): array;

    /**
     * Place an order for a specified cart for external user.
     *
     * @param string $platform
     * @param string $cartId The cart ID.
     * @param int|null $customerId
     * @param \Magento\Quote\Api\Data\PaymentInterface|null $paymentMethod
     * @param \Pratech\Order\Api\Data\CampaignInterface|null $campaign
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function placeExternalOrder(
        string                                    $platform,
        string                                    $cartId,
        int|null                                  $customerId,
        \Magento\Quote\Api\Data\PaymentInterface  $paymentMethod = null,
        \Pratech\Order\Api\Data\CampaignInterface $campaign = null
    ): array;

    /**
     * Get Order Details by Order Increment Id.
     *
     * @param string $platform
     * @param int $id The order ID.
     * @return array Order Details.
     * @throws \Exception
     */
    public function getOrderDetails(string $platform, int $id): array;

    /**
     * Cancel order.
     *
     * @param string $platform
     * @param int $id The Order Increment ID.
     * @return array
     * @throws \Exception
     */
    public function cancelOrder(string $platform, int $id): array;

    /**
     * Get brand images
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBrandImages(): array;

    /**
     * Get inventory about product by product sku
     *
     * @param string $sku
     * @param string $platform
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getInventoryBySku(string $sku, string $platform): array;

    /**
     * Get orders by customer mobile number.
     *
     * @param string $mobileNumber
     * @param string $platform
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getOrdersByCustomerMobileNumber(
        string $mobileNumber,
        string $platform,
        string $orderId = ''
    ): array;
}
