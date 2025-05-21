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

namespace Pratech\Filters\Model;

use Magento\Framework\Model\AbstractModel;

class QuickFilters extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'quick_filters';

    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\QuickFilters::class);
    }
}
