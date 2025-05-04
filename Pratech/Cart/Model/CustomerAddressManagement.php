<?php

namespace Pratech\Cart\Model;

use Pratech\Cart\Api\CustomerAddressManagementInterface;

/**
 * Customer Address Management to empower customer cart address information.
 */
class CustomerAddressManagement implements CustomerAddressManagementInterface
{
    /**
     * Customer Address Management Constructor.
     *
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $customerShippingInformationManagement
     */
    public function __construct(
        private \Magento\Checkout\Api\ShippingInformationManagementInterface $customerShippingInformationManagement
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveAddressInformation(
        int                                                     $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        return $this->customerShippingInformationManagement->saveAddressInformation($cartId, $addressInformation);
    }
}
