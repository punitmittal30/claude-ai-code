<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Model;

use Exception;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\CustomerBalance\Model\ResourceModel\Balance;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Model\Data\Response;
use Pratech\Customer\Api\CustomerRepositoryInterface;
use Pratech\Customer\Helper\Data;
use Pratech\StoreCredit\Helper\Config as StoreCreditConfig;

/**
 * Customer Model to expose customer api endpoints.
 */
class Customer implements CustomerRepositoryInterface
{
    /**
     * Customer API Resource Constant
     */
    public const CUSTOMER_API_RESOURCE = 'customer';

    /**
     * Customer Address API Resource Constant
     */
    public const CUSTOMER_ADDRESS_API_RESOURCE = 'customer_address';

    /**
     * Order API Resource Constant
     */
    public const ORDER_API_RESOURCE = 'order';

    /**
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param Data $customerHelper
     * @param Balance $customerBalance
     * @param Response $response
     * @param Logger $logger
     * @param StoreCreditConfig $storeCreditConfig
     */
    public function __construct(
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private Data                                              $customerHelper,
        private Balance                                           $customerBalance,
        private Response                                          $response,
        private Logger                                            $logger,
        private StoreCreditConfig                                 $storeCreditConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCustomerById(int $customerId): array
    {
        $customer = $this->customerRepository->getById($customerId);

        $customerData = [
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'gender' => $customer->getGender(),
            'dob' => $customer->getDob(),
            'email' => $customer->getEmail(),
            'mobile_number' => $customer->getCustomAttribute('mobile_number')->getValue(),
            'billing_address' => $this->customerHelper->getAddressByAddressId($customer->getDefaultBilling()),
            'shipping_address' => $this->customerHelper->getAddressByAddressId($customer->getDefaultShipping())
        ];
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            $customerData
        );
    }

    /**
     * @inheritDoc
     */
    public function setCustomerById(int $customerId, $customer): array
    {
        $customerObj = $this->customerRepository->getById($customerId);
        $customerObj->setFirstname($customer->getFirstname());
        $customerObj->setLastname($customer->getLastname());
        $customerObj->setGender($customer->getGender());
        $customerObj->setDob($customer->getDob());
        try {
            $customer = $this->customerRepository->save($customerObj);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
            throw new LocalizedException(__($exception->getMessage()));
        }
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            [
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'dob' => $customer->getDob(),
                'gender' => $customer->getGender(),
                'mobile_number' => $customer->getCustomAttribute('mobile_number')->getValue()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerAddresses(int $customerId): array
    {
        $addresses = [];
        /** @var AddressInterface[] $customerAddresses */
        $customerAddresses = $this->customerRepository->getById($customerId)->getAddresses();

        foreach ($customerAddresses as $customerAddress) {
            $addresses[] = [
                'id' => $customerAddress->getId(),
                'firstname' => $customerAddress->getFirstname(),
                'lastname' => $customerAddress->getLastname(),
                'street' => $customerAddress->getStreet(),
                'region' => $customerAddress->getRegion()->getRegion(),
                'region_id' => $customerAddress->getRegionId(),
                'region_code' => $customerAddress->getRegion()->getRegionCode(),
                'city' => $customerAddress->getCity(),
                'state' => $customerAddress->getRegion()->getRegion(),
                'pincode' => $customerAddress->getPostcode(),
                'contact_number' => $customerAddress->getTelephone(),
                'is_default_billing' => $customerAddress->isDefaultBilling() ?? false,
                'is_default_shipping' => $customerAddress->isDefaultShipping() ?? false,
                'customer_address_type' => $customerAddress->getCustomAttribute('customer_address_type') ?
                    $customerAddress->getCustomAttribute('customer_address_type')->getValue() : '',
                'email_address' => $customerAddress->getCustomAttribute('email_address') ?
                    $customerAddress->getCustomAttribute('email_address')->getValue() : ''
            ];
        }
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_ADDRESS_API_RESOURCE,
            $addresses
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerOrders(SearchCriteriaInterface $searchCriteria): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->customerHelper->getCustomerOrders($searchCriteria)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerStoreCredit(int $customerId): array
    {
        $isReviewed = false;
        $reviewCashbackAmount = 0;
        $conversionRate = $this->storeCreditConfig->getConversionRate();
        $isFirstReviewEnabled = $this->storeCreditConfig->isFirstReviewCashbackEnabled();
        if ($isFirstReviewEnabled) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $isReviewed = $customer->getCustomAttribute('is_reviewed')
                    ? $customer->getCustomAttribute('is_reviewed')->getValue()
                    : false;
                $reviewCashbackAmount = $this->storeCreditConfig->getFirstReviewCashbackAmount();
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        $connection = $this->customerBalance->getConnection();

        $select = $connection->select()
            ->from($this->customerBalance->getMainTable(), 'amount')
            ->where('customer_id = ?', $customerId);

        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            [
                "balance" => $connection->fetchOne($select),
                "conversion_rate" => $conversionRate,
                "is_first_review" => !$isReviewed,
                "review_cashback_amount" => $reviewCashbackAmount
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function addCustomerAddress(AddressInterface $address): array
    {
        $addressRepository = $this->customerHelper->getAddressRepository();
        $savedAddress = $addressRepository->save($address);
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_ADDRESS_API_RESOURCE,
            $this->customerHelper->formatAddress($savedAddress)
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteAddressByAddressId(int $addressId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_ADDRESS_API_RESOURCE,
            [
                "is_deleted" => $this->customerHelper->deleteAddressByAddressId($addressId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function updateCustomerAddress(AddressInterface $address): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_ADDRESS_API_RESOURCE,
            $this->customerHelper->updateCustomerAddress($address)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerOrderById(int $customerId, int $orderId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_ADDRESS_API_RESOURCE,
            $this->customerHelper->getCustomerOrderById($customerId, $orderId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerPurchasedProducts(int $customerId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            $this->customerHelper->getCustomerPurchasedProducts($customerId, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getOrderHistory(SearchCriteriaInterface $searchCriteria): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::ORDER_API_RESOURCE,
            $this->customerHelper->getOrderHistory($searchCriteria)
        );
    }

    /**
     * @inheritDoc
     */
    public function viewOrderDetails(int $customerId, int $orderId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            $this->customerHelper->viewOrderDetails($customerId, $orderId)
        );
    }

    /**
     * @inheritDoc
     */
    public function getBlockedCustomers(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::CUSTOMER_API_RESOURCE,
            $this->customerHelper->getBlockedCustomers()
        );
    }
}
