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

namespace Pratech\Order\Model;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Pratech\Base\Model\Data\Response;
use Pratech\Order\Api\Data\CampaignInterface;
use Pratech\Order\Api\Data\CancelOrderRequestItemInterface;
use Pratech\Order\Api\Data\ConfirmOrderRequestItemInterface;
use Pratech\Order\Api\OrderRepositoryInterface;
use Pratech\Order\Helper\Order;

/**
 * Order Repository class to expose api for order creation.
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Constant for ORDER API RESOURCE
     */
    public const ORDER_API_RESOURCE = 'order';

    /**
     * Order Repository Constructor
     *
     * @param Order $orderHelper
     * @param Response $response
     */
    public function __construct(
        protected Order    $orderHelper,
        protected Response $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOrder(int $id): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->orderHelper->getOrder($id)
        );
    }

    /**
     * @inheritDoc
     */
    public function placeGuestOrder(
        string            $cartId,
        int|null          $customerId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->orderHelper->placeGuestOrder($cartId, $customerId, $paymentMethod, $campaign)
        );
    }

    /**
     * @inheritDoc
     */
    public function placeCustomerOrder(
        int               $cartId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->orderHelper->placeCustomerOrder($cartId, $paymentMethod, $campaign)
        );
    }

    /**
     * @inheritDoc
     */
    public function confirmOrder(ConfirmOrderRequestItemInterface $confirmOrderRequest): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->orderHelper->confirmOrder($confirmOrderRequest)
        );
    }

    /**
     * @inheritDoc
     */
    public function confirmCodOrder(int $orderId, string $source): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->orderHelper->confirmCodOrder($orderId, $source)
        );
    }

    /**
     * @inheritDoc
     */
    public function cancelOrder(int $id, int $customerId, string $reason): array
    {
        $isCanceled = $this->orderHelper->cancelOrder($id, $customerId, $reason);

        if ($isCanceled) {
            return $this->response->getResponse(
                200,
                'success',
                self::ORDER_API_RESOURCE,
                [
                    "is_cancel" => true
                ]
            );
        } else {
            return $this->response->getResponse(
                200,
                'Order cannot be canceled',
                self::ORDER_API_RESOURCE,
                [
                    "is_cancel" => false
                ]
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function deliverOrder(int $orderId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            [
                "is_delivered" => $this->orderHelper->deliverOrder($orderId)
            ]
        );
    }

    /**
     * Add Order Comment.
     *
     * @param int $id
     * @param OrderStatusHistoryInterface $statusHistory
     * @param mixed|array $refund
     * @inheritDoc
     */
    public function addOrderComment(
        int                         $id,
        OrderStatusHistoryInterface $statusHistory,
        mixed                       $refund = []
    ): bool {
        return $this->orderHelper->addOrderComment($id, $statusHistory, $refund);
    }

    /**
     * @inheritDoc
     */
    public function packedOrder(int $orderId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            [
                "is_packed" => $this->orderHelper->packedOrder($orderId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function cancelPartialOrder(int $orderId, array $items): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            [
                'is_cancelled' => $this->orderHelper->cancelPartialOrder($orderId, $items)
            ]
        );
    }
}
