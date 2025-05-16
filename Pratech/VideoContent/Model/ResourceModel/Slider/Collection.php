<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Model\ResourceModel\Slider;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'slider_id';

    /**
     * Constructor to map model and resource model.
     */
    protected function _construct()
    {
        $this->_init(
            \Pratech\VideoContent\Model\Slider::class,
            \Pratech\VideoContent\Model\ResourceModel\Slider::class
        );
    }
}
