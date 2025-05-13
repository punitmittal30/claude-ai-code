<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Pratech\ThirdPartyIntegration\Api\ExternalCustomerInterface;

class ExternalCustomer implements ExternalCustomerInterface
{
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CollectionFactory           $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCustomerByEmail(string $email): array
    {
        $customer = $this->customerRepository->get($email);
        return [
            'customer_id' => (int)$customer->getId(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'mobile_number' => $customer->getCustomAttribute('mobile_number')
                ? $customer->getCustomAttribute('mobile_number')->getValue() : '',
            'classification' => 'VIP',
            'address' => $this->getCustomerAddresses($customer->getAddresses())
        ];
    }

    /**
     * Get Customer Addresses.
     *
     * @param array $customerAddresses
     * @return array
     */
    public function getCustomerAddresses(array $customerAddresses): array
    {
        $addresses = [];
        foreach ($customerAddresses as $customerAddress) {
            $addresses[] = [
                'firstname' => $customerAddress->getFirstname(),
                'lastname' => $customerAddress->getLastname(),
                'street' => $customerAddress->getStreet(),
                'city' => $customerAddress->getCity(),
                'pincode' => $customerAddress->getPostcode(),
                'contact_number' => $customerAddress->getTelephone()
            ];
        }
        return $addresses;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerByMobileNumber(string $mobileNumber): array
    {
        $customer = $this->collectionFactory->create()
            ->addAttributeToFilter('mobile_number', ['eq' => $mobileNumber])
            ->getFirstItem();

        return [
            'customer_id' => (int)$customer->getId(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'mobile_number' => $mobileNumber,
            'classification' => 'VIP',
            'address' => $this->getCustomerAddresses($customer->getAddresses())
        ];
    }
}
