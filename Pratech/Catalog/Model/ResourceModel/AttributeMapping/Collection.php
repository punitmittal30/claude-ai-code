<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\ResourceModel\AttributeMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Catalog\Model\AttributeMapping;
use Pratech\Catalog\Model\ResourceModel\AttributeMapping as AttributeMappingResourceModel;

/**
 * Attribute Mapping collection class
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'mapping_id';

    protected function _construct()
    {
        $this->_init(AttributeMapping::class, AttributeMappingResourceModel::class);
    }
}
