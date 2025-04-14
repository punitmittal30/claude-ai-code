<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * QuickFilters Resource Model class
 */
class QuickFilters extends AbstractDb
{

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $quickFiltersTable;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->quickFiltersTable = $this->getTable('quick_filters');
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('quick_filters', 'entity_id');
    }
}
