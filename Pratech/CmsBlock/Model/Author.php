<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

 namespace Pratech\CmsBlock\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Author Model Class
 */
class Author extends AbstractModel
{
    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Pratech\CmsBlock\Model\ResourceModel\Author::class);
    }
}
