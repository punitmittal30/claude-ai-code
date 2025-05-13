<?php
/**
 * Hyugalife_CatalogAttributes
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyugalife\CatalogAttributes
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
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
class AddIsCODRestrictedForProductAttributes implements DataPatchInterface
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
            'is_cod_restricted' => 'Is COD Restricted'
        ];
    }

    /**
     * Get Field Options
     *
     * @param string $attributeLabel
     * @return array
     */
    private function getFieldOptions(string $attributeLabel): array
    {
        return [
            'type' => 'int',
            'frontend' => '',
            'label' => $attributeLabel,
            'input' => 'boolean',
            'backend' => Product\Attribute\Backend\Boolean::class,
            'source' => Product\Attribute\Source\Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
            'unique' => false
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
