<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Model\ResourceModel\Comment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'comment_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\Blog\Model\Comment::class,
            \Pratech\Blog\Model\ResourceModel\Comment::class
        );
    }
}
