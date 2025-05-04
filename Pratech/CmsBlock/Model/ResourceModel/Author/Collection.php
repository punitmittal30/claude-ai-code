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

namespace Pratech\CmsBlock\Model\ResourceModel\Author;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\CmsBlock\Model\Author;
use Pratech\CmsBlock\Model\ResourceModel\Author as AuthorResourceModel;

/**
 * Author collection class
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'author_id';

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Author::class, AuthorResourceModel::class);
    }
}
