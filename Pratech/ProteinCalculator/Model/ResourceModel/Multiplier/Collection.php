<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ProteinCalculator\Model\ResourceModel\Multiplier;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\ProteinCalculator\Model\Multiplier;
use Pratech\ProteinCalculator\Model\ResourceModel\Multiplier as MultiplierResource;

/**
 * Multiplier collection class
 *
 * Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize model and resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Multiplier::class, MultiplierResource::class);
    }
}
