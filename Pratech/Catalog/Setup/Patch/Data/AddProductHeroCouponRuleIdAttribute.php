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
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pratech\Base\Logger\Logger;

class AddProductHeroCouponRuleIdAttribute implements DataPatchInterface
{
    /**
     * Entity Type value
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param Logger $apiLogger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CategorySetupFactory     $categorySetupFactory,
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
     * Apply
     *
     * @return void
     */
    public function apply(): void
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $categorySetup->addAttribute(
                Product::ENTITY,
                'hero_coupon_rule_id',
                [
                    'input' => 'text',
                    'type' => 'int',
                    'label' => 'Hero Coupon Rule Id',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'used_for_promo_rules' => true,
                    'frontend_class' => 'validate-digits',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'unique' => false,
                    'is_used_in_grid' => true,
                    'is_filterable_in_grid' => true,
                ]
            );
            $attributeSetId = $categorySetup->getDefaultAttributeSetId(Product::ENTITY);
            $categorySetup->addAttributeToSet(
                Product::ENTITY,
                $attributeSetId,
                'General',
                'hero_coupon_rule_id'
            );
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Aliases
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
