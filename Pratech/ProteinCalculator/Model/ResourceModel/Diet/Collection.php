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

namespace Pratech\ProteinCalculator\Model\ResourceModel\Diet;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\ProteinCalculator\Model\Diet;
use Pratech\ProteinCalculator\Model\ResourceModel\Diet as DietResource;

/**
 * Diet collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Diet::class, DietResource::class);
    }
}
