<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Plugin\Model\ResourceModel\Review;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Review Collection
 */
class Collection extends \Magento\Review\Model\ResourceModel\Review\Collection
{
    /**
     * Initialize select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        AbstractCollection::_initSelect();
        $this->getSelect()->join(
            ['detail' => $this->getReviewDetailTable()],
            'main_table.review_id = detail.review_id',
            [
                'detail_id', 'store_id', 'title', 'detail', 'nickname',
                'customer_id', 'power_review', 'position', 'keywords'
            ]
        );
        return $this;
    }
}
