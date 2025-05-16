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

namespace Pratech\Refund\Model\ResourceModel\Refund;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Constructor to map model and resource model.
     */
    protected function _construct(): void
    {
        $this->_init(
            \Pratech\Refund\Model\Refund::class,
            \Pratech\Refund\Model\ResourceModel\Refund::class
        );
    }
}
