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
use Pratech\Cart\Api\GuestCouponManagementInterface;
use Pratech\Cart\Helper\Coupon as CouponHelper;

/**
 * Guest Coupon Management Class to expose guest cart coupon api endpoints.
 */
class GuestCouponManagement implements GuestCouponManagementInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * CART API RESOURCE
     */
    private const COUPON_API_RESOURCE = 'coupon';

    /**
     * Guest Coupon Management Constructor
     *
     * @param CouponHelper $couponHelper
     * @param Response $response
     */
    public function __construct(
        private CouponHelper $couponHelper,
        private Response     $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getGuestCoupon(string $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            [
                "coupon_code" => $this->couponHelper->getGuestCoupon($cartId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function setGuestCoupon(string $cartId, string $couponCode, string $platform = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->setGuestCoupon($cartId, $couponCode, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function removeGuestCoupon(string $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            [
                "removed" => $this->couponHelper->removeGuestCoupon($cartId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuestCouponListing(string $platform = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getGuestCouponListing($platform)
        );
    }
}
