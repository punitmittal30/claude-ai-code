<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Api;

interface OrderRepositoryInterface
{
    /**
     * Place an order for a specified cart for guest user.
     *
     * @param string $cartId The cart ID.
     * @param int|null $customerId
     * @param \Magento\Quote\Api\Data\PaymentInterface|null $paymentMethod
     * @param \Pratech\Order\Api\Data\CampaignInterface|null $campaign
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeGuestOrder(
        string                                    $cartId,
        int|null                                  $customerId,
        \Magento\Quote\Api\Data\PaymentInterface  $paymentMethod = null,
        \Pratech\Order\Api\Data\CampaignInterface $campaign = null
    ): array;

    /**
     * Places an order for a specified cart for customer.
     *
     * @param int $cartId The cart ID.
     * @param \Magento\Quote\Api\Data\PaymentInterface|null $paymentMethod
     * @param \Pratech\Order\Api\Data\CampaignInterface|null $campaign
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeCustomerOrder(
        int                                       $cartId,
        \Magento\Quote\Api\Data\PaymentInterface  $paymentMethod = null,
        \Pratech\Order\Api\Data\CampaignInterface $campaign = null
    ): array;

    /**
     * Loads a specified order.
     *
     * @param int $id The order ID.
     * @return array Order Details.
     */
    public function getOrder(int $id): array;

    /**
     * Confirm an order.
     *
     * @param \Pratech\Order\Api\Data\ConfirmOrderRequestItemInterface $confirmOrderRequest
     * @return array Order Details.
     */
    public function confirmOrder(\Pratech\Order\Api\Data\ConfirmOrderRequestItemInterface $confirmOrderRequest): array;

    /**
     * Confirm an COD order.
     *
     * @param int $orderId
     * @param string $source
     * @return array
     */
    public function confirmCodOrder(int $orderId, string $source): array;

    /**
     * Cancels a specified order.
     *
     * @param int $id The order ID.
     * @param int $customerId
     * @param string $reason
     * @return array
     * @throws \Exception
     */
    public function cancelOrder(int $id, int $customerId, string $reason): array;

    /**
     * Mark order as delivered by Vinculum.
     *
     * @param int $orderId
     * @return array
     */
    public function deliverOrder(int $orderId): array;

    /**
     * Add Order Comment.
     *
     * @param int $id
     * @param \Magento\Sales\Api\Data\OrderStatusHistoryInterface $statusHistory
     * @param mixed $refund
     * @return bool
     */
    public function addOrderComment(
        int $id,
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $statusHistory,
        mixed $refund = []
    ): bool;

    /**
     * Mark order as packed item by Vinculum.
     *
     * @param int $orderId
     * @return array
     */
    public function packedOrder(int $orderId): array;

    /**
     * Cancel Order Items
     *
     * @param int $orderId The order ID.
     * @param \Pratech\Order\Api\Data\CancelOrderRequestItemInterface[] $items
     * @return array
     * @throws \Exception
     */
    public function cancelPartialOrder(int $orderId, array $items): array;
}
