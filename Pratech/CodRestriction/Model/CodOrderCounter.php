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

namespace Pratech\CodRestriction\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * CodOrderCounter Model class
 */
class CodOrderCounter extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'cod_order_counter';

    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\CodOrderCounter::class);
    }
}
