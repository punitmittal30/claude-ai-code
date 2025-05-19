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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Pratech\Cart\Api\CustomerPaymentManagementInterface;
use Pratech\Cart\Api\GuestPaymentManagementInterface;

/**
 * Payment method management interface for guest carts.
 */
class GuestPaymentManagement implements GuestPaymentManagementInterface
{
    /**
     * @param CustomerPaymentManagementInterface $customerPaymentManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        private CustomerPaymentManagementInterface $customerPaymentManagement,
        private QuoteIdMaskFactory                 $quoteIdMaskFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function savePaymentInformation(string $cartId, PaymentInterface $method): string
    {
        return $this->set($cartId, $method);
    }

    /**
     * Save Payment Information for Guest.
     *
     * @param string $cartId
     * @param PaymentInterface $method
     * @return string Payment method
     * @throws InvalidTransitionException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function set(string $cartId, PaymentInterface $method): string
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->customerPaymentManagement->savePaymentInformation($quoteIdMask->getQuoteId(), $method);
    }
}
