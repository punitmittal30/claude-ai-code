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

namespace Pratech\Catalog\Controller\Adminhtml\Product;

/**
 * Controller to Delete Products
 */
class MassDelete extends \Magento\Catalog\Controller\Adminhtml\Product\MassDelete
{
    /**
     * Authorization Function
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::delete_products');
    }
}
