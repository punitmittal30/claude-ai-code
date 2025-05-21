<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Sales\Model\Order;
use Zend_Db_Expr;

/**
 * Qty Sold Class to fetch Product Qty Sold over N Days which is configurable from Admin.
 */
class QtySold extends AbstractDb
{

    /**
     * Event prefix value
     *
     * @var string
     */
    protected string $_eventPrefix = 'qty_sold';

    /**
     * Event object value
     *
     * @var string
     */
    protected string $_eventObject = 'resource';

    /**
     * Get Qty Sold
     *
     * @param string $daysCriteria
     * @return array
     */
    public function getQtySold(string $daysCriteria): array
    {
        $connection = $this->getConnection();

        $select = $connection->select();

        $columns = [
            'store_id' => 'source_table.store_id',
            'product_id' => 'order_item.product_id',
            'product_name' => new Zend_Db_Expr('MIN(order_item.name)'),
            'product_price' => new Zend_Db_Expr(
                'MIN(IF(order_item_parent.base_price, order_item_parent.base_price, order_item.base_price))' .
                '* MIN(source_table.base_to_global_rate)'
            ),
            'qty_ordered' => new Zend_Db_Expr('SUM(order_item.qty_ordered)'),
        ];

        $select->group(['order_item.product_id']);

        $select->from(
            ['source_table' => $this->getTable('sales_order')],
            $columns
        )->joinInner(
            ['order_item' => $this->getTable('sales_order_item')],
            'order_item.order_id = source_table.entity_id',
            []
        )->joinLeft(
            ['order_item_parent' => $this->getTable('sales_order_item')],
            'order_item.parent_item_id = order_item_parent.item_id',
            []
        )->where(
            'source_table.state != ?',
            Order::STATE_CANCELED
        )->where(
            'source_table.created_at > ?',
            $daysCriteria
        );

        return $connection->fetchAll($select);
    }

    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('sales_order', 'entity_id');
    }
}
