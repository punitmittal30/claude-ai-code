<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pratech\Base\Logger\Logger;

/**
 * Add Product Additional Label Class to store additional label content
 */
class AddProductAdditionalLabelAttribute implements DataPatchInterface
{
    /**
     * Add Product Additional Label Attribute Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Logger $apiLogger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private Logger                   $apiLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'additional_label',
                [
                    'input' => 'text',
                    'type' => 'text',
                    'label' => __('Additional Label'),
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'used_for_promo_rules' => true,
                    'frontend_class' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'unique' => false,
                    'is_used_in_grid' => true,
                    'is_filterable_in_grid' => true,
                ]
            );
            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                'Default',
                'General', // group
                'additional_label',
                1000 // sort order
            );
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
    }
}
