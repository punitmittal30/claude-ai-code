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

namespace Pratech\Catalog\Plugin\Indexer;

class Fulltext
{
    /**
     * Disable reindex on product save
     *
     * @param \Magento\CatalogSearch\Model\Indexer\Fulltext $subject
     * @param int $id
     * @return void
     */
    public function aroundExecuteRow(
        \Magento\CatalogSearch\Model\Indexer\Fulltext $subject,
        callable $proceed,
        int $id
    ): void {
        return;
    }

    /**
     * Disable reindex on product save
     *
     * @param \Magento\CatalogSearch\Model\Indexer\Fulltext $subject
     * @param int[] $entityIds
     * @return void
     */
    public function aroundExecute(
        \Magento\CatalogSearch\Model\Indexer\Fulltext $subject,
        callable $proceed,
        $entityIds
    ): void {
        return;
    }
}
