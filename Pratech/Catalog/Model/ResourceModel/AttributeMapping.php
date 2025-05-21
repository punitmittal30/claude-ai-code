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

namespace Pratech\Catalog\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Attribute Mapping Resource Model Class
 */
class AttributeMapping extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('pratech_attribute_mapping', 'mapping_id');
    }

    /**
     * Load By Category Id
     *
     * @param  AbstractModel $object
     * @param  int           $categoryId
     * @return $this
     */
    public function loadByCategoryId(AbstractModel $object, $categoryId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable())->where('category_id = ?', $categoryId);

        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }

        return $this;
    }
}
