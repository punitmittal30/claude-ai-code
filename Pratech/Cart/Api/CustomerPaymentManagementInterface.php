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
 * Payment method management interface for customer carts.
 */
interface CustomerPaymentManagementInterface
{
    /**
     * Save Payment Information for Customer.
     *
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $method
     * @return string
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function savePaymentInformation(int $cartId, \Magento\Quote\Api\Data\PaymentInterface $method): string;
}
