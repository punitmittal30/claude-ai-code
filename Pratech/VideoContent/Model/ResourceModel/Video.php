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

namespace Pratech\VideoContent\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Video Resource Model class
 */
class Video extends AbstractDb
{

    /**
     * @var string
     */
    protected $_idFieldName = 'video_id';

    /**
     * @var string
     */
    protected string $VideoTable;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);
        $this->VideoTable = $this->getTable('video_entity');
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('video_entity', 'video_id');
    }
}
