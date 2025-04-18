<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\Attribute\Backend\Image;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pratech\Base\Logger\Logger;

/**
 * Add Category Thumbnail Attribute Class to store category thumbnail image
 */
class AddCategoryCustomAttributes implements DataPatchInterface
{
    /**
     * Add Category Thumbnail Attribute Constructor
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
     * @inheritDoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(Category::ENTITY, 'show_bubble', [
                'type' => 'int',
                'label' => 'Show Bubble',
                'input' => 'select',
                'source' => Boolean::class,
                'sort_order' => 4,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
            ]);
            $eavSetup->addAttribute(Category::ENTITY, 'top_brands', [
                'type' => 'int',
                'label' => 'Top Brands',
                'input' => 'select',
                'source' => Boolean::class,
                'sort_order' => 5,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
            ]);
            $eavSetup->addAttribute(Category::ENTITY, 'category_thumbnail', [
                'type' => 'varchar',
                'label' => 'Category Thumbnail',
                'input' => 'image',
                'backend' => Image::class,
                'required' => false,
                'sort_order' => 1,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information'
            ]);
            $eavSetup->addAttribute(Category::ENTITY, 'founder_description', [
                'type' => 'text',
                'label' => 'Founder Description',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'group' => 'General Information'
            ]);
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
    }
}
