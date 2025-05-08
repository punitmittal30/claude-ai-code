<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LinkedProduct extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('linked_configurable_product', null);
    }

    /**
     * Get Linked prdoducts id
     *
     * @param int $productId
     * @return array
     * @throws LocalizedException
     */
    public function getLinkedProducts(int $productId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['product_id', 'linked_product_id'])
            ->where('product_id = :product_id OR linked_product_id = :product_id');

        $bind = ['product_id' => $productId];
        $result = $connection->fetchAll($select, $bind);

        $linked = [];
        foreach ($result as $row) {
            if ((int)$row['product_id'] === $productId) {
                $linked[] = (int)$row['linked_product_id'];
            } elseif ((int)$row['linked_product_id'] === $productId) {
                $linked[] = (int)$row['product_id'];
            }
        }

        return array_unique($linked);
    }

    /**
     * Insert linked product
     *
     * @param int $productId
     * @param int $linkedProductId
     * @return void
     * @throws LocalizedException
     */
    public function insertLink(int $productId, int $linkedProductId): void
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            ['product_id' => $productId, 'linked_product_id' => $linkedProductId],
            []
        );
    }

    /**
     * Save Linked Products
     *
     * @param int $productId
     * @param array $linkedProductIds
     * @return void
     * @throws LocalizedException
     */
    public function saveLinks(int $productId, array $linkedProductIds): void
    {
        $this->deleteLinksForProduct($productId);

        foreach ($linkedProductIds as $linkedProductId) {
            if ($productId !== (int)$linkedProductId) {
                $this->insertLink($productId, (int)$linkedProductId);
                $this->insertLink((int)$linkedProductId, $productId);
            }
        }
    }

    /**
     * Delete Linked Products
     *
     * @param int $productId
     * @return void
     * @throws LocalizedException
     */
    public function deleteLinksForProduct(int $productId): void
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getMainTable(),
            ['product_id = ? OR linked_product_id = ?' => $productId]
        );
    }
}
