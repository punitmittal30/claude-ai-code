<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;

/**
 * Customer Repository Interface to expose customer api.
 */
interface CustomerRepositoryInterface
{
    /**
     * Get customer by Customer ID.
     *
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerById(int $customerId): array;

    /**
     * Create or update a customer.
     *
     * @param int $customerId
     * @param CustomerInterface $customer
     * @return array
     * @throws InputException If bad input is provided
     * @throws InputMismatchException If the provided email is already used
     * @throws LocalizedException
     */
    public function setCustomerById(int $customerId, \Magento\Customer\Api\Data\CustomerInterface $customer): array;

    /**
     * Retrieve customer addresses for the given customerId.
     *
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException If the customer Id is invalid
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerAddresses(int $customerId): array;

    /**
     * Set Customer Address By Customer ID
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addCustomerAddress(\Magento\Customer\Api\Data\AddressInterface $address): array;

    /**
     * Get Customer Order Based on Order ID
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getCustomerOrders(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array;

    /**
     * Get Customer Store Credit Amount By Customer ID
     *
     * @param int $customerId
     * @return array
     */
    public function getCustomerStoreCredit(int $customerId): array;

    /**
     * Delete Customer Address By Address ID
     *
     * @param int $addressId
     * @return array true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteAddressByAddressId(int $addressId): array;

    /**
     * Update Customer Address
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateCustomerAddress(\Magento\Customer\Api\Data\AddressInterface $address): array;

    /**
     * Get Customer Order By Order ID.
     *
     * @param int $customerId
     * @param int $orderId
     * @return array
     * @throws \Exception
     */
    public function getCustomerOrderById(int $customerId, int $orderId): array;

    /**
     * Get Customer Purchased Products By Customer ID
     *
     * @param int $customerId
     * @param int|null $pincode
     * @return array
     */
    public function getCustomerPurchasedProducts(int $customerId, int $pincode = null): array;

    /**
     * Get Customer Order History
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getOrderHistory(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): array;

    /**
     * Get Customer Order Detail By Order ID.
     *
     * @param int $customerId
     * @param int $orderId
     * @return array
     * @throws \Exception
     */
    public function viewOrderDetails(int $customerId, int $orderId): array;

    /**
     * Get Blocked Customers
     *
     * @return array
     * @throws \Exception
     */
    public function getBlockedCustomers(): array;
}
