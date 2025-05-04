<?php

namespace Pratech\Customer\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Model\Config as EavConfig;
use Pratech\Base\Logger\Logger;

class AddNewCustomerAddressField implements DataPatchInterface
{
    public const EMAIL_ADDRESS = 'email_address';

    public const ADDRESS_TYPE = 'address_type';

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param EavConfig $eavConfig
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Logger $apiLogger
     */
    public function __construct(
        private EavSetupFactory          $eavSetupFactory,
        private EavConfig                $eavConfig,
        private ModuleDataSetupInterface $moduleDataSetup,
        private Logger                   $apiLogger
    ) {
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Apply Function.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->createEmailAddressAttribute();
        $this->createAddressTypeAttribute();
    }

    /**
     * Create Email Address Attribute.
     *
     * @return void
     */
    public function createEmailAddressAttribute(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::EMAIL_ADDRESS,
                [
                    'label' => 'Email Address',
                    'input' => 'text',
                    'visible' => true,
                    'required' => false,
                    'position' => 200,
                    'sort_order' => 200,
                    'system' => false
                ]
            );

            $emailAddressAttribute = $this->eavConfig->getAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::EMAIL_ADDRESS
            );
            $emailAddressAttribute->setData(
                'used_in_forms',
                [
                    'adminhtml_customer_address',
                    'customer_address_edit',
                    'customer_register_address'
                ]
            );
            $emailAddressAttribute->save();
        } catch (\Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Create Address Type Attribute.
     *
     * @return void
     */
    public function createAddressTypeAttribute(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::ADDRESS_TYPE,
                [
                    'label' => 'Address Type',
                    'input' => 'text',
                    'visible' => true,
                    'required' => false,
                    'position' => 200,
                    'sort_order' => 200,
                    'system' => false
                ]
            );

            $addressTypeAttribute = $this->eavConfig->getAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::ADDRESS_TYPE
            );
            $addressTypeAttribute->setData(
                'used_in_forms',
                [
                    'adminhtml_customer_address',
                    'customer_address_edit',
                    'customer_register_address'
                ]
            );
            $addressTypeAttribute->save();
        } catch (\Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
