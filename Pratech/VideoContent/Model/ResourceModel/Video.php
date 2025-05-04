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

use Magento\Framework\Model\AbstractModel;
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
     * @var string
     */
    protected $sliderVideoMapping;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->VideoTable = $this->getTable('video_entity');
        $this->sliderVideoMapping = $this->getTable('video_slider_mapping');
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

    /**
     * Get Slider ID
     *
     * @param int $videoId
     * @return array
     */
    public function getSliderId(int $videoId): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->sliderVideoMapping, 'slider_id')
            ->where('video_id = ?', $videoId);

        return $adapter->fetchCol($select);
    }

    /**
     * After Save
     *
     * @param AbstractModel $object
     * @return Video
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->_updateSliderVideo($object);

        return parent::_afterSave($object);
    }

    /**
     * Update Slider Video
     *
     * @param \Pratech\VideoContent\Model\Video $video
     * @return $this
     */
    protected function _updateSliderVideo(\Pratech\VideoContent\Model\Video $video)
    {
        $video->setIsChangedSliderList(false);
        $id = $video->getId();

        $slider = $video->getAssignToSlider();
        $oldSlider = $video->getSliderId($id);

        $adapter = $this->getConnection();

        if (!empty($oldSlider)) {
            $condition = ['slider_id IN(?)' => $oldSlider, 'video_id=?' => $id];
            $adapter->delete($this->sliderVideoMapping, $condition);
        }

        if (!empty($slider)) {
            $data = [
                'video_id' => (int)$id,
                'slider_id' => (int)$slider
            ];
            $adapter->insertMultiple($this->sliderVideoMapping, $data);
        }

        if (!empty($insert) || !empty($delete)) {
            $slider->setIsChangedSliderList(true);
        }

        return $this;
    }
}
