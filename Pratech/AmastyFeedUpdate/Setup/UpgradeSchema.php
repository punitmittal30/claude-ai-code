<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Update Table Schema for Amasty Feed
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addProductBaseUrlColumns($setup);
        }

        $setup->endSetup();
    }

    /**
     * Add Product Base Url Column in Feed Table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function addProductBaseUrlColumns(SchemaSetupInterface $setup): void
    {
        $table = $setup->getTable('amasty_feed_entity');
        $connection = $setup->getConnection();

        $connection->addColumn(
            $table,
            'product_base_url',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'Product Base Url'
            ]
        );
    }
}
