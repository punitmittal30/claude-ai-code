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

namespace Pratech\Coupon\Plugin\Model;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Pratech\Coupon\Helper\CouponValidator;
use Pratech\Coupon\Helper\Data;

class CouponManagement extends \Magento\Quote\Model\CouponManagement
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CouponValidator $couponValidator
     * @param Data $data
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        private CouponValidator $couponValidator,
        private Data            $data
    ) {
        parent::__construct($quoteRepository);
    }

    /**
     * @inheritDoc
     */
    public function set($cartId, $couponCode)
    {
        /** @var  Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('The "%1" Cart doesn\'t contain products.', $cartId));
        }
        if (!$quote->getStoreId()) {
            throw new NoSuchEntityException(__('Cart isn\'t assigned to correct store'));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);

        try {
            $quote->setCouponCode($couponCode);
            $this->quoteRepository->save($quote->collectTotals());
        } catch (Exception $exception) {
            if ($this->data->isEnabled()) {
                $msg = $this->couponValidator->validate($couponCode, $quote->getCustomerId());
                if (!empty($msg)) {
                    throw new NoSuchEntityException(__($msg));
                }
            }
            throw $exception;
        }
        if ($quote->getCouponCode() != $couponCode) {
            if ($this->data->isEnabled()) {
                $msg = $this->couponValidator->validate($couponCode, $quote->getCustomerId());
                if (!empty($msg)) {
                    throw new NoSuchEntityException(__($msg));
                }
            }
        }
        return true;
    }
}
