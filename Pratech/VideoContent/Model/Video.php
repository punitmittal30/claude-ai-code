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

namespace Pratech\VideoContent\Model;

use Magento\Framework\Model\AbstractModel;

class Video extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'video';

    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Video::class);
    }
}
