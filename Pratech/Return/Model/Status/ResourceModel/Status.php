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

namespace Pratech\Return\Model\Status\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\Return\Api\Data\StatusInterface;

class Status extends AbstractDb
{
    public const TABLE_NAME = 'sales_order_return_status';

    public function unsetPreviousInitialStatus($statusId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [StatusInterface::IS_INITIAL => 0],
            [StatusInterface::STATUS_ID . ' <> ?' => (int)$statusId]
        );
    }

    public function unsetAutoEvent($autoEvent, $state, $statusId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [StatusInterface::AUTO_EVENT => 0],
            [
                StatusInterface::STATUS_ID . ' <> ?' => (int)$statusId,
                StatusInterface::STATE . ' = ?' => (int)$state,
                StatusInterface::AUTO_EVENT . ' = ?' => (int)$autoEvent
            ]
        );
    }

    /**
     * @param $grid
     *
     * @return array
     * @throws LocalizedException
     */
    public function getGridStatuses($grid)
    {
        $select = $this->getConnection()->select()->from(['statuses' => $this->getMainTable()])
            ->where('statuses.' . StatusInterface::GRID . ' = ?', (int)$grid)
            ->reset(Select::COLUMNS)
            ->columns('statuses.' . StatusInterface::STATUS_ID);

        if ($statusIds = $this->getConnection()->fetchCol($select)) {
            return $statusIds;
        }

        return [];
    }

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, StatusInterface::STATUS_ID);
    }
}
