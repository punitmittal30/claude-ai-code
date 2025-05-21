<?php
/**
 * Pratech_CodRestriction
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CodRestriction\Model\ResourceModel\CodOrderCounter;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\CodRestriction\Model\CodOrderCounter;

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
            CodOrderCounter::class,
            \Pratech\CodRestriction\Model\ResourceModel\CodOrderCounter::class
        );
    }
}
