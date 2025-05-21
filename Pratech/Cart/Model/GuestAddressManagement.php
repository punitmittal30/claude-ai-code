<?php

namespace Pratech\Cart\Model;

use Pratech\Cart\Api\GuestAddressManagementInterface;

/**
 * Guest Address Management to empower guest cart address information.
 */
class GuestAddressManagement implements GuestAddressManagementInterface
{
    /**
     * @param \Magento\Checkout\Api\GuestShippingInformationManagementInterface $guestShippingInformationManagement
     */
    public function __construct(
        private \Magento\Checkout\Api\GuestShippingInformationManagementInterface $guestShippingInformationManagement
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveAddressInformation(
        string                                                  $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        return $this->guestShippingInformationManagement->saveAddressInformation($cartId, $addressInformation);
    }
}
