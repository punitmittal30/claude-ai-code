<?php
/**
 * Pratech_Refund
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Refund
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Refund\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Refund Resource Model class to get sales_order_payment_refund table data.
 */
class Refund extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected string $salesOrderPaymentRefundTable;

    /**
     * Construct Method
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->salesOrderPaymentRefundTable = $this->getTable('sales_order_payment_refund');
    }

    /**
     * Get Refund ID
     *
     * @param int $orderId
     * @return array
     */
    public function isRefundInitiated(int $orderId): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->salesOrderPaymentRefundTable, 'status')
            ->where('order_id = ?', $orderId)
            ->where('status = ?', 'refund.triggered');

        return $adapter->fetchCol($select);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('sales_order_payment_refund', 'entity_id');
    }
}
