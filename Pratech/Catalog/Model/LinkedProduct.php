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
namespace Pratech\Catalog\Model;

use Magento\Framework\Model\AbstractModel;

class LinkedProduct extends AbstractModel
{
    /**
     * Linked Product Model
     *
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\LinkedProduct::class);
    }
}
