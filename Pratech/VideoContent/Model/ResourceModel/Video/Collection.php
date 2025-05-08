<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Model\ResourceModel\Video;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\VideoContent\Model\Video;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'video_id';

    /**
     * Constructor to map model and resource model.
     */
    protected function _construct()
    {
        $this->_init(
            Video::class,
            \Pratech\VideoContent\Model\ResourceModel\Video::class
        );
    }
}
