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

namespace Pratech\ThirdPartyIntegration\Model;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Pratech\Base\Model\Data\Response;
use Pratech\Order\Api\Data\CampaignInterface;
use Pratech\ThirdPartyIntegration\Api\ExternalOrderInterface;
use Pratech\ThirdPartyIntegration\Helper\ExternalOrder as ExternalOrderHelper;

class ExternalOrder implements ExternalOrderInterface
{
    /**
     * Constant for ORDER API RESOURCE
     */
    public const ORDER_API_RESOURCE = 'order';

    /**
     * Constant for External API RESOURCE
     */
    public const EXTERNAL_API_RESOURCE = 'external';

    /**
     * External Order Constructor
     *
     * @param ExternalOrderHelper $externalOrderHelper
     * @param Response $response
     */
    public function __construct(
        private ExternalOrderHelper $externalOrderHelper,
        private Response            $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createEmptyCart(string $platform): array
    {
        return $this->externalOrderHelper->createEmptyCart($platform);
    }

    /**
     * @inheritDoc
     */
    public function addItemToGuestCart(string $platform, CartItemInterface $cartItem): array
    {
        return $this->externalOrderHelper->addItemToGuestCart($platform, $cartItem);
    }

    /**
     * @inheritDoc
     */
    public function saveAddressInformation(
        string                       $platform,
        string                       $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface {
        return $this->externalOrderHelper->saveAddressInformation($platform, $cartId, $addressInformation);
    }

    /**
     * @inheritDoc
     */
    public function getProductById(string $platform, int $productId): array
    {
        return $this->externalOrderHelper->getProductById($platform, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getProductsByCategoryId(int $categoryId, string $platform, int $pageSize, int $currentPage): array
    {
        return $this->externalOrderHelper->getProductsByCategoryId($categoryId, $platform, $pageSize, $currentPage);
    }

    /**
     * @inheritDoc
     */
    public function placeExternalOrder(
        string            $platform,
        string            $cartId,
        ?int              $customerId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->externalOrderHelper->placeExternalOrder(
                $platform,
                $cartId,
                $customerId,
                $paymentMethod,
                $campaign
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getOrderDetails(string $platform, int $id): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->externalOrderHelper->getOrderDetails($platform, $id)
        );
    }

    /**
     * @inheritDoc
     */
    public function cancelOrder(string $platform, int $id): array
    {
        $isCanceled = $this->externalOrderHelper->cancelOrder($platform, $id);

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
    public function getBrandImages(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::EXTERNAL_API_RESOURCE,
            $this->externalOrderHelper->getBrandImages()
        );
    }

    /**
     * @inheritDoc
     */
    public function getInventoryBySku(string $sku, string $platform): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::EXTERNAL_API_RESOURCE,
            $this->externalOrderHelper->getInventoryBySku($sku, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getOrdersByCustomerMobileNumber(string $mobileNumber, string $platform, string $orderId = ''): array
    {
        list($message, $orderData) = $this->externalOrderHelper->getOrdersByCustomerMobileNumber(
            $mobileNumber,
            $platform,
            $orderId
        );
        return $this->response->getResponse(
            200,
            $message,
            self::ORDER_API_RESOURCE,
            $orderData
        );
    }
}
