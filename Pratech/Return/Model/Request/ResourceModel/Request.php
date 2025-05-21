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
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Model\Status\ResourceModel\Status;
use Zend_Db_Expr;

class Request extends AbstractDb
{
    public const TABLE_NAME = 'sales_order_return_request';

    /**
     * Get Request ID By Hash.
     *
     * @param  $hash
     * @return false|int
     * @throws LocalizedException
     */
    public function getRequestIdByHash($hash)
    {
        $select = $this->getConnection()->select()->from(['request' => $this->getMainTable()])
            ->reset(Select::COLUMNS)
            ->columns('request.' . RequestInterface::REQUEST_ID);

        if ($requestId = $this->getConnection()->fetchOne($select)) {
            return (int)$requestId;
        }

        return false;
    }

    /**
     * Get Request Count By Statuses.
     *
     * @param  $statuses
     * @return int
     * @throws LocalizedException
     */
    public function getRequestCountByStatuses($statuses)
    {
        if (empty($statuses)) {
            return 0;
        }

        $select = $this->getConnection()->select()
            ->from(['request' => $this->getMainTable()], new Zend_Db_Expr('count(*)'))
            ->where('request.' . RequestInterface::STATUS . ' IN (?)', $statuses);

        return (int)$this->getConnection()->fetchOne($select);
    }

    /**
     * Get Total By State.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getTotalByState()
    {
        $select = $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(Select::COLUMNS)
            ->columns(['total' => new Zend_Db_Expr('count(*)')])
            ->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'main_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                ['status_table.state']
            )->group('status_table.state');

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            foreach ($rows as $row) {
                $result[$row['state']] = $row['total'];
            }
        }

        return $result;
    }

    /**
     * Get Manager Requests Count.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getManagerRequestsCount()
    {
        $select = $this->getConnection()->select()->from(['main_table' => $this->getMainTable()])
            ->reset(Select::COLUMNS)
            ->columns(['total' => 'count(*)', 'main_table.manager_id'])
            ->joinInner(
                ['status_table' => $this->getTable(Status::TABLE_NAME)],
                'main_table.status = status_table.status_id AND status_table.state IN (0, 1, 2)',
                ['status_table.state']
            )->group('main_table.manager_id');

        $result = [];
        if ($rows = $this->getConnection()->fetchAll($select)) {
            foreach ($rows as $row) {
                $result[$row['manager_id']] = $row['total'];
            }
        }

        return $result;
    }

    /**
     * Construct.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, RequestInterface::REQUEST_ID);
    }
}
