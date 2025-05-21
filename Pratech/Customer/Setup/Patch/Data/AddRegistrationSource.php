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

namespace Pratech\Customer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pratech\Customer\Model\Config\Source\RegistrationSource;
use Psr\Log\LoggerInterface;

class AddRegistrationSource implements DataPatchInterface
{
    /**
     * Customer Attribute Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param LoggerInterface $logger
     * @param Attribute $attributeResource
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private EavSetupFactory          $eavSetupFactory,
        private Config                   $eavConfig,
        private LoggerInterface          $logger,
        private Attribute                $attributeResource,
        private ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * Get Dependencies
     *
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Apply.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $this->addRegistrationSourceAttribute();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Add Mobile Number Attribute
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function addRegistrationSourceAttribute(): void
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'registration_source',
            [
                'type' => 'varchar',
                'label' => 'Registration Source',
                'input' => 'select',
                'source' => RegistrationSource::class,
                'required' => 0,
                'visible' => 1,
                'user_defined' => 1,
                'sort_order' => 1000,
                'backend' => '',
                'default' => '',
                'position' => 1000,
                'system' => 0,
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'is_searchable_in_grid' => 1,
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);

        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'registration_source');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);

        $attribute->setData('used_in_forms', [
            'adminhtml_customer',
            'adminhtml_customer_address',
            'customer_account_edit',
            'customer_address_edit',
            'customer_register_address',
            'customer_account_create'
        ]);

        $this->attributeResource->save($attribute);
    }

    /**
     * Get Aliases
     *
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
