<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
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
 * Class to create Custom Product Attribute of type boolean using Data Patch.
 */
class AddTextProductAttributes implements DataPatchInterface
{
    /**
     * Entity Type value
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory     $categorySetupFactory
     * @param LoggerInterface          $logger
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

        foreach ($this->getCustomProductAttributes() as $attributeCode => $attributeLabel) {
            try {
                $categorySetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    $this->getFieldOptions($attributeLabel)
                );
                $attributeSetId = $categorySetup->getDefaultAttributeSetId(Product::ENTITY);
                $categorySetup->addAttributeToSet(
                    Product::ENTITY,
                    $attributeSetId,
                    'General',
                    $attributeCode
                );
            } catch (LocalizedException|Zend_Validate_Exception $e) {
                $this->logger->error($e->getMessage() . __METHOD__);
            }
        }
    }

    /**
     * Get Custom Product Attributes
     *
     * @return string[]
     */
    private function getCustomProductAttributes(): array
    {
        return [
            'price_per_gram_protein' => 'Price per gram Protein',
            'price_per_100_gram' => 'Price per 100 gram',
            'price_per_count' => 'Price per count',
            'price_per_100_ml' => 'Price per 100 ml',
            'protein_per_serving' => 'Protein Per Serving',
            'number_of_serving_for_price_per_serving' => 'Number of Serving (for price per serving)',
            'total_volume_in_ml' => 'Total Volume (in ml)',
            'total_number_of_count' => 'Total number of count',
        ];
    }

    /**
     * Get Field Options
     *
     * @param  string $attributeLabel
     * @return array
     */
    private function getFieldOptions(string $attributeLabel): array
    {
        return [
            'input' => 'text',
            'type' => 'text',
            'label' => $attributeLabel,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'used_for_promo_rules' => true,
            'frontend_class' => 'validate-number',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'unique' => false,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
        ];
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
