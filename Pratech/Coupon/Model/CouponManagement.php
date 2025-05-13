<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Coupon\Api\CouponManagementInterface;
use Pratech\Coupon\Helper\Coupon as CouponHelper;

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
    public function getCouponListingForCustomer(int $quoteId, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getCouponListingForCustomer($quoteId, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getHeroCouponForCustomer(int $quoteId, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getHeroCouponForCustomer($quoteId, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerAppliedCoupons(int $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getCustomerAppliedCoupons($cartId)
        );
    }

    /**
     * @inheritDoc
     */
    public function applyCustomerCoupons(int $cartId, string $couponCode, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->applyCustomerCoupons($cartId, $couponCode, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function removeCustomerCoupons(int $cartId, string $couponCode): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->removeCustomerCoupons($cartId, $couponCode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCouponListingForGuest(string $quoteId, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getCouponListingForGuest($quoteId, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getHeroCouponForGuest(string $quoteId, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getHeroCouponForGuest($quoteId, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuestAppliedCoupons(string $cartId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getGuestAppliedCoupons($cartId)
        );
    }

    /**
     * @inheritDoc
     */
    public function applyGuestCoupons(string $cartId, string $couponCode, string $platform): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->applyGuestCoupons($cartId, $couponCode, $platform)
        );
    }

    /**
     * @inheritDoc
     */
    public function removeGuestCoupons(string $cartId, string $couponCode): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->removeGuestCoupons($cartId, $couponCode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCouponsByRuleId(int $ruleId, int $pageSize, int $currentPage): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::COUPON_API_RESOURCE,
            $this->couponHelper->getCouponsByRuleId($ruleId, $pageSize, $currentPage)
        );
    }
}
