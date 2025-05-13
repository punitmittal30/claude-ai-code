<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ReviewRatings\Model\ResourceModel\Media;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'media_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\ReviewRatings\Model\Media::class,
            \Pratech\ReviewRatings\Model\ResourceModel\Media::class
        );
    }
}
