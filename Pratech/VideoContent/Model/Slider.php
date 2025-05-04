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

namespace Pratech\VideoContent\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Slider Model class
 */
class Slider extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'video_slider';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Slider::class);
    }

    /**
     * Get Video IDs
     *
     * @return array
     */
    public function getVideoIds()
    {
        if (!$this->hasData('video_id')) {
            $ids = $this->getResource()->getVideoIds($this);

            $this->setData('video_id', $ids);
        }

        return (array) $this->getData('video_id');
    }
}
