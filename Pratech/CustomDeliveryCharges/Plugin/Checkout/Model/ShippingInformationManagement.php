<?php
/**
 * Pratech_CustomDeliveryCharges
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CustomDeliveryCharges
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CustomDeliveryCharges\Plugin\Checkout\Model;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Pratech\CustomDeliveryCharges\Helper\Data;

/**
 * Shipping Information Management class to save delivery charges data in quote.
 */
class ShippingInformationManagement
{
    /**
     * @param QuoteRepository $quoteRepository
     * @param Data $deliveryChargesHelper
     */
    public function __construct(
        private QuoteRepository $quoteRepository,
        private Data            $deliveryChargesHelper
    ) {
    }

    /**
     * Shipping Information Management constructor
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return void
     * @throws NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        int                                                   $cartId,
        ShippingInformationInterface                          $addressInformation
    ): void {
        $deliveryCharges = $addressInformation->getExtensionAttributes()->getDeliveryCharges();
        $quote = $this->quoteRepository->getActive($cartId);
        if ($deliveryCharges) {
            $amount = $this->deliveryChargesHelper->getDeliveryChargesAmount();
            $quote->setDeliveryCharges($amount);
        } else {
            $quote->setDeliveryCharges(null);
        }
    }
}
