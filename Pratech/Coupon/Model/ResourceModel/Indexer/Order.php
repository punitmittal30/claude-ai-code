<?php
/**
 * Pratech_Coupon
 *
 * @category  XML
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\Coupon\Model\ResourceModel\Indexer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Order
{
    /**
     * @var string[]
     */
    private $cancelledOrderStatus = [
        'payment_failed',
        'canceled',
        'closed'
    ];

    /**
     * Order Constructor
     *
     * @param OrderResource $salesOrderResource
     */
    public function __construct(
        private OrderResource $salesOrderResource
    ) {
    }

    /**
     * Retrieve Customer Ids By Order Ids.
     *
     * @param array $ids
     * @return array
     * @throws LocalizedException
     */
    public function retrieveCustomerIdsByOrderIds(array $ids): array
    {
        $connection = $this->salesOrderResource->getConnection();
        $select = $connection->select()->from(
            $this->salesOrderResource->getMainTable(),
            ['customer_id']
        )->where(
            $this->salesOrderResource->getIdFieldName() . ' IN (?)',
            $ids
        )->where(
            'customer_id IS NOT NULL'
        )->group(
            'customer_id'
        );

        return $connection->fetchCol($select);
    }

    /**
     * Retrieve order data such as orders count & orders base sum for customers
     *
     * @param array $ids customer ids
     *
     * @return array data with customer_id, order count and order sum
     * @throws LocalizedException
     */
    public function retrieveIndexData(array $ids): array
    {
        $connection = $this->salesOrderResource->getConnection();

        if (empty($ids)) {
            $customersCondition = ['o.customer_id IS NOT NULL'];
        } else {
            $customersCondition = ['o.customer_id IN (?)', $ids];
        }

        $select = $connection->select()
            ->from(
                ['o' => $this->salesOrderResource->getMainTable()],
                ['customer_id', new \Zend_Db_Expr(
                    'COUNT(CASE WHEN o.platform IN (3,4) THEN 1 END) as a'
                ),
                    new \Zend_Db_Expr('COUNT(CASE WHEN o.platform IN (1,2) THEN 1 END) as w')]
            )->where(
                ...$customersCondition
            )->where(
                'o.state NOT IN (?)',
                $this->cancelledOrderStatus
            )->group(
                'customer_id'
            );
        return (array)$connection->fetchAll($select);
    }
}
