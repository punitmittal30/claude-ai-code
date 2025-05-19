<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Exception;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AddIsReviewedAttribute implements DataPatchInterface
{
    /**
     * Add IsReviewed Attribute Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param Attribute $attributeResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private Config                   $eavConfig,
        private Attribute                $attributeResource,
        private LoggerInterface          $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        try {
            $this->moduleDataSetup->startSetup();

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            // Create the custom attribute
            $eavSetup->addAttribute(
                Customer::ENTITY,
                'is_reviewed',
                [
                    'type' => 'int',
                    'label' => 'Is First Review Submitted',
                    'input' => 'text',
                    'default' => 0,
                    'source' => '',
                    'visible' => false,
                    'required' => false,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'position' => 999,
                    'system' => 0,
                    'is_visible_in_grid' => 1,
                    'is_filterable_in_grid' => 1,
                    'is_searchable_in_grid' => 1
                ]
            );

            $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
            $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);

            $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'is_reviewed');
            $attribute->setData('attribute_set_id', $attributeSetId);
            $attribute->setData('attribute_group_id', $attributeGroupId);

            $attribute->setData('used_in_forms', [
                'adminhtml_customer'
            ]);

            $this->attributeResource->save($attribute);

            $this->moduleDataSetup->endSetup();
        } catch (Exception $e) {
            $this->logger->error('Error during attribute creation: ' . $e->getMessage());
        }
    }
}
