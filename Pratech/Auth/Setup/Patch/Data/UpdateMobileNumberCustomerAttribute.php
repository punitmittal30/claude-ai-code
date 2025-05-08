<?php
/**
 * Pratech_Auth
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Auth
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Auth\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;

class UpdateMobileNumberCustomerAttribute implements DataPatchInterface
{

    /**
     * UpdateMobileNumberCustomerAttribute constructor.
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $eavConfig
     * @param Attribute $attributeResource
     */
    public function __construct(
        private EavSetupFactory          $eavSetupFactory,
        private ModuleDataSetupInterface $moduleDataSetup,
        private Config                   $eavConfig,
        private Attribute                $attributeResource,
    ) {
    }

    /**
     * Modify EAV tables to update mobile number attribute is used in grid value
     *
     * @return void
     */
    public function apply()
    {
        // set new resource model paths
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'mobile_number',
            [
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'is_searchable_in_grid' => 1,
            ]
        );
        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);

        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'mobile_number');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);

        $this->attributeResource->save($attribute);
    }

     /**
      * Get Aliases
      *
      * @return array|string[]
      */
    public function getAliases()
    {
        return [];
    }

    /**
     * Get Dependencies
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [
            \Pratech\Auth\Setup\Patch\Data\AddMobileNumberCustomerAttribute::class
        ];
    }
}
