<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Model\ResourceModel\CreditPoints;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\StoreCredit\Model\CreditPoints;
use Pratech\StoreCredit\Model\ResourceModel\CreditPoints as CreditResourceModel;

/**
 * Credit points collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'storecredit_id';

    /**
     * Construct Method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CreditPoints::class, CreditResourceModel::class);
    }
}
