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
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Pratech\Cart\Api\CustomerPaymentManagementInterface;

/**
 * Payment method management interface for guest carts.
 */
class CustomerPaymentManagement implements CustomerPaymentManagementInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        private CartRepositoryInterface $quoteRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function savePaymentInformation(int $cartId, PaymentInterface $method): string
    {
        return $this->set($cartId, $method);
    }

    /**
     * Save Payment Information for Customer.
     *
     * @param int $cartId
     * @param PaymentInterface $method
     * @return string
     * @throws InvalidTransitionException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function set(int $cartId, PaymentInterface $method): string
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($cartId);
        $quote->setTotalsCollectedFlag(false);
        $method->setChecks([
            AbstractMethod::CHECK_USE_CHECKOUT,
            AbstractMethod::CHECK_USE_FOR_COUNTRY,
            AbstractMethod::CHECK_USE_FOR_CURRENCY,
            AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
        ]);

        $paymentData = $method->getData();
        $payment = $quote->getPayment();
        $payment->importData($paymentData);

        $quote->save();
        return $quote->getPayment()->getMethod();
    }
}
