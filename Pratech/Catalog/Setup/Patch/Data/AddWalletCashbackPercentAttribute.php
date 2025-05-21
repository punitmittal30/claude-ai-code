<?php
/**
 * Hyugalife_CatalogAttributes
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyugalife\CatalogAttributes
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare (strict_types=1);

namespace Pratech\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate_Exception;

/**
 * Class to create Custom Product Attribute of type text using Data Patch.
 */
class AddWalletCashbackPercentAttribute implements DataPatchInterface
{
    /**
     * Entity Type value
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CategorySetupFactory     $categorySetupFactory,
        private LoggerInterface          $logger
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
                'wallet_cashback_percent',
                [
                    'input' => 'text',
                    'type' => 'int',
                    'label' => 'Wallet Cashback Percent',
                    'visible' => true,
                    'required' => false,
                    'default' => 0,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'comparable' => false,
                    'used_for_promo_rules' => true,
                    'frontend_class' => 'validate-digits',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'unique' => false,
                    'is_used_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'used_for_sort_by' => true
                ]
            );
            $attributeSetId = $categorySetup->getDefaultAttributeSetId(Product::ENTITY);
            $categorySetup->addAttributeToSet(
                Product::ENTITY,
                $attributeSetId,
                'General',
                'wallet_cashback_percent'
            );
        } catch (LocalizedException|Zend_Validate_Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
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
