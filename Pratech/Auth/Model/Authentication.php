<?php
/**
 * Pratech_Auth
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Auth
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Auth\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Pratech\Auth\Api\AuthenticationManagementInterface;
use Pratech\Base\Model\Data\Response;
use Pratech\Customer\Model\Config\Source\RegistrationSource;
use Pratech\StoreCredit\Helper\Config as StoreCreditConfig;

/**
 * Authentication class to expose customer authentication api endpoints
 */
class Authentication implements AuthenticationManagementInterface
{
    private const MOBILE_LENGTH = 10;

    /**
     * Constructor
     *
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param CollectionFactory $customerCollectionFactory
     * @param AccountManagementInterface $accountManagement
     * @param Response $response
     * @param ManagerInterface $eventManager
     * @param RegistrationSource $registrationSource
     * @param StoreCreditConfig $storeCreditConfig
     */
    public function __construct(
        private CustomerTokenServiceInterface $customerTokenService,
        private CollectionFactory             $customerCollectionFactory,
        private AccountManagementInterface    $accountManagement,
        private Response                      $response,
        private ManagerInterface              $eventManager,
        private RegistrationSource            $registrationSource,
        private StoreCreditConfig             $storeCreditConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function register(CustomerInterface $customer, $password = null): array
    {
        $mobileNumber = $customer->getCustomAttribute('mobile_number')
            ? $customer->getCustomAttribute('mobile_number')->getValue()
            : null;

        if ($mobileNumber === null || strlen($mobileNumber) != self::MOBILE_LENGTH) {
            throw new InputMismatchException(
                __('Mobile number should be exactly 10 digit.')
            );
        }

        $registrationSource = $customer->getCustomAttribute('registration_source')
            ? $customer->getCustomAttribute('registration_source')->getValue()
            : null;

        $registrationSource = $this->registrationSource->validateRegistrationSource($registrationSource)
            ? $registrationSource
            : 'other';

        $customerData = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('mobile_number', ['eq' => $mobileNumber])
            ->getFirstItem();

        if ($customerData->isEmpty()) {

            if ($customer->getDob()) {
                if (!strtotime($customer->getDob())) {
                    $customer->setDob('1995-01-01');
                }
            }

            $customer->setCustomAttribute('registration_source', $registrationSource);
            $customer = $this->accountManagement->createAccount($customer, $password);
            $extensionAttributes = $customer->getExtensionAttributes();
            $extensionAttributes->setCustomerToken($this->customerTokenService
                ->createCustomerAccessToken($customer->getEmail(), $password));
            $extensionAttributes->setPassword($password);
            $customer->setExtensionAttributes($extensionAttributes);

            // Dispatch the customer_register_success event
            $this->eventManager->dispatch('customer_register_success', ['customer' => $customer]);
        } else {
            $customer = $customerData;
        }

        $customerData = [
            "id" => $customer->getId(),
            "email" => $customer->getEmail(),
            "firstname" => $customer->getFirstname(),
            "lastname" => $customer->getLastname(),
            "gender" => $customer->getGender(),
            "dob" => $customer->getDob(),
            "mobile_number" => $mobileNumber,
            "customer_token" => $customer->getExtensionAttributes()
                ? $customer->getExtensionAttributes()->getCustomerToken()
                : null,
            "password" => $customer->getExtensionAttributes()
                ? $customer->getExtensionAttributes()->getPassword()
                : null
        ];

        $isRegistrationCashbackEnabled = $this->storeCreditConfig->isRegistrationCashbackEnabled();
        $cashbackAmount = $this->storeCreditConfig->getRegistrationCashbackAmountBySource($registrationSource);

        if($isRegistrationCashbackEnabled && $cashbackAmount > 0){
            $customerData["hcash_details"] = [
                "is_hcash_credited" => true,
                "hcash_amount" => $cashbackAmount
            ];
        }

        return $this->response->getResponse(
            '200',
            'success',
            'customer',
            $customerData
        );
    }
}
