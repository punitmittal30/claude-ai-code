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

namespace Pratech\Return\Model\Reason\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\Return\Api\Data\ReasonInterface;

class Reason extends AbstractDb
{
    public const TABLE_NAME = 'sales_order_return_reason';

    /**
     * Construct.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, ReasonInterface::REASON_ID);
    }
}
