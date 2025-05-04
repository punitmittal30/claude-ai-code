<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\Request\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Model\Reason\ResourceModel\Reason;
use Pratech\Return\Model\Status\ResourceModel\Status;

class RequestItem extends AbstractDb
{
    public const TABLE_NAME = 'sales_order_return_request_item';

    /**
     * @param  $requestId
     * @param  $requestItemIds
     * @return void
     * @throws LocalizedException
     */
    public function removeDeletedItems($requestId, $requestItemIds)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                RequestItemInterface::REQUEST_ID . ' = ?' => (int)$requestId,
                RequestItemInterface::REQUEST_ITEM_ID . ' NOT IN (?)' => $requestItemIds
            ]
        );
    }

    /**
     * Get Top Reasons.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getTop5Reasons()
    {
        $select = $this->getNotArchivedItemsSelect()->columns(['total' => 'count(*)'])->joinLeft(
            ['reason_table' => $this->getTable(Reason::TABLE_NAME)],
            'reason_table.reason_id = main_table.reason_id',
            ['reason_table.title']
        )->group('main_table.reason_id')->order('total DESC')->limit(5);

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            return $rows;
        }

        return $result;
    }

    /**
     * Get Not Archived Items Select.
     *
     * @return Select
     * @throws LocalizedException
     */
    public function getNotArchivedItemsSelect()
    {
        return $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(Select::COLUMNS)
            ->joinInner(
                ['request_table' => $this->getTable(Request::TABLE_NAME)],
                'main_table.request_id = request_table.request_id',
                []
            )->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'request_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                []
            );
    }

    /**
     * Get Return Items Base Price.
     *
     * @return float|int
     * @throws LocalizedException
     */
    public function getReturnItemsBasePrice()
    {
        $select = $this->getNotArchivedItemsSelect()->columns(
            ['SUM(COALESCE(configurable_order_items.base_price, order_items.base_price)*main_table.qty)']
        )->joinLeft(
            ['order_items' => $this->getTable('sales_order_item')],
            'order_items.item_id = main_table.order_item_id',
            []
        )->joinLeft(
            ['configurable_order_items' => $this->getTable('sales_order_item')],
            'configurable_order_items.item_id = order_items.parent_item_id',
            []
        );

        if ($result = $this->getConnection()->fetchCol($select)) {
            return (double)$result[0];
        }

        return 0;
    }

    /**
     * Construct.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, RequestItemInterface::REQUEST_ITEM_ID);
    }
}
