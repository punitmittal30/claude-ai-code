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

namespace Pratech\ThirdPartyIntegration\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddCustomerClassificationAttribute implements DataPatchInterface
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
     * Apply Patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $this->addClassificationAttribute();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Add Classification Attribute
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function addClassificationAttribute(): void
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'classification',
            [
                'type' => 'varchar',
                'label' => 'Classification',
                'input' => 'text',
                'required' => 0,
                'visible' => 1,
                'user_defined' => 1,
                'sort_order' => 1000,
                'position' => 1000,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'system' => 0
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);

        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'classification');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);

        $attribute->setData('used_in_forms', [
            'adminhtml_customer',
            'customer_account_edit',
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
