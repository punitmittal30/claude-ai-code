<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Model\ResourceModel\FiltersPosition;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Filters\Model\ResourceModel\FiltersPosition;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Constructor to map model and resource model.
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\Filters\Model\FiltersPosition::class,
            FiltersPosition::class
        );
    }
}
