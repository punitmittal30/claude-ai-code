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

 namespace Pratech\Coupon\Model\ResourceModel;

use Pratech\Coupon\Model\Indexer\PurchaseHistory;
use Pratech\Coupon\Model\Indexer\PurchaseHistory\IndexStructure;
use Magento\Framework\App\ObjectManager;

/**
 * Class for Data precessing from DB
 */
class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const ALL = 'all';
    public const TABLE_NAME = 'sales_order';

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string                                            $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null,
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    /**
     * Get Validation Data.
     *
     * @param int    $customerId
     * @param string $attribute
     *
     * @return float
     */
    public function getValidationData($customerId, $attribute)
    {
        $connection = $this->getConnection();
        $columns = [];

        if ($attribute === 'order_num_app') {
            $columns = [IndexStructure::APP_ORDERS_COUNT];
        } elseif ($attribute === 'order_num_web') {
            $columns = [IndexStructure::WEB_ORDERS_COUNT];
        }

        $select = $connection->select()
            ->from(['i' => $this->getTable(PurchaseHistory::INDEXER_ID)], $columns)
            ->where('i.customer_id = ?', $customerId);

        $result = (float)$connection->fetchOne($select);

        return $result;
    }
}
