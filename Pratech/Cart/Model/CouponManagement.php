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
use Pratech\Cart\Api\CouponManagementInterface;
use Pratech\Cart\Helper\Coupon as CouponHelper;

/**
 * Coupon Management Class to expose coupon endpoints.
 */
class CouponManagement implements CouponManagementInterface
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
     * Coupon Management Constructor
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
    public function getCustomerCoupon(int $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            [
                "coupon_code" => $this->couponHelper->getCustomerCoupon($cartId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function setCustomerCoupon(int $cartId, string $couponCode, string $platform = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->setCustomerCoupon($cartId, $couponCode, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function removeCustomerCoupon(int $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            [
                "removed" => $this->couponHelper->removeCustomerCoupon($cartId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerCouponListing(string $platform = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getCustomerCouponListing($platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getHeroCoupons(string $platform = null): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getHeroCoupons($platform)
        );
    }
}
