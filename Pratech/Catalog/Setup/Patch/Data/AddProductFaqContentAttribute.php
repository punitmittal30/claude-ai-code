<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Pratech\Catalog\Ui\DataProvider\Product\Form\Modifier\FaqContent;
use Pratech\Base\Logger\Logger;

/**
 * Add Product FAQ Attribute Class to store faq content
 */
class AddProductFaqContentAttribute implements DataPatchInterface
{
    /**
     * Add Product FAQ Attribute Constructor
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
        $eavSetup = $this->eavSetupFactory->create();
        try {
            $eavSetup->addAttribute(
                Product::ENTITY,
                FaqContent::PRODUCT_ATTRIBUTE_CODE,
                [
                    'label' => 'FAQ',
                    'type'  => 'text',
                    'default'  => '',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 9999,
                    'user_defined' => true,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'used_in_product_listing' => true,
                    'visible_on_front' => true,
                    'visible' => true
                ]
            );
            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                'Default',
                'General', // group
                FaqContent::PRODUCT_ATTRIBUTE_CODE,
                9999 // sort order
            );
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
        }
    }
}
