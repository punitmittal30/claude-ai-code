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

namespace Pratech\Catalog\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Attribute Mapping Model Class
 */
class AttributeMapping extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Pratech\Catalog\Model\ResourceModel\AttributeMapping');
    }

    /**
     * Load By Category Id
     *
     * @param  int $categoryId
     * @return $this
     */
    public function loadByCategoryId($categoryId)
    {
        $this->_getResource()->loadByCategoryId($this, $categoryId);
        return $this;
    }
}
