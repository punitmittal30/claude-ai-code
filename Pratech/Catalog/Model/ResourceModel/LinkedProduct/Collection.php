<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\ResourceModel\LinkedProduct;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Catalog\Model\LinkedProduct as Model;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
