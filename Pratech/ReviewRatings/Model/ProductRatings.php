<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Model;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Product Rating Class to fetch product rating for product card.
 */
class ProductRatings extends AbstractDb
{
    /**
     * Constant for REVIEW ENTITY SUMMARY
     */
    public const REVIEW_ENTITY_SUMMARY = 'review_entity_summary';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::REVIEW_ENTITY_SUMMARY, 'primary_id');
    }

    /**
     * Get product rating for product card
     *
     * @param int $productId
     * @param int $storeId
     * @return mixed
     */
    public function getProductRatings(int $productId, int $storeId)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(self::REVIEW_ENTITY_SUMMARY)
            ->where('entity_pk_value = ?', $productId)
            ->where('store_id = ?', $storeId);

        return $adapter->fetchRow($select);
    }
}
