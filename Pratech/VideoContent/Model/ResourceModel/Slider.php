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

namespace Pratech\VideoContent\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\VideoContent\Model\Slider as SliderModel;

/**
 * Slider Resource Model class
 */
class Slider extends AbstractDb
{

    /**
     * Mapping table -> video_slider_mapping
     */
    public const MAPPING_TABLE = 'video_slider_mapping';

    /**
     * Slide Entity ID constant
     */
    public const SLIDE_ENTITY_ID = 'slider_id';

    /**
     * Video Entity ID constant
     */
    public const VIDEO_ENTITY_ID = 'video_id';

    /**
     * Get Videos
     *
     * @param int|null $sliderId
     * @return array
     */
    public function getVideos(?int $sliderId)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::VIDEO_ENTITY_ID)
            ->where('slider_id = ?', (int)$sliderId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Videos Not Assigned
     *
     * @return array
     */
    public function getVideosNotAssigned()
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::VIDEO_ENTITY_ID)
            ->distinct(self::VIDEO_ENTITY_ID);
        $videoDistinct = $adapter->fetchCol($select);

        $select = $adapter->select()
            ->from('video_entity', self::VIDEO_ENTITY_ID);
        $videoAll = $adapter->fetchCol($select);

        return array_diff($videoAll, $videoDistinct);
    }

    /**
     * Get Video IDs
     *
     * @param SliderModel $slider
     * @return array
     */
    public function getVideoIds(SliderModel $slider)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::VIDEO_ENTITY_ID)
            ->where('slider_id = ?', (int)$slider->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('video_slider', 'slider_id');
    }

    /**
     * After Save
     *
     * @param AbstractModel $object
     * @return Slider
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->_updateSliderVideo($object);
        return parent::_afterSave($object);
    }

    /**
     * Update Slider Video
     *
     * @param SliderModel $slider
     * @return $this
     */
    protected function _updateSliderVideo(SliderModel $slider)
    {
        $id = $slider->getSliderId();
        $videos = $this->getPostedVideos($slider->getPostedData());
        $oldVideos = $slider->getVideoIds() ? $slider->getVideoIds() : [];

        if (empty($videos) && !isset($slider->getPostedData()['video_slider_mapping'])) {
            return $this;
        }

        $insert = array_diff($videos, $oldVideos);
        $delete = array_diff($oldVideos, $videos);
        $adapter = $this->getConnection();

        if (!empty($delete)) {
            $condition = ['video_id IN(?)' => $delete, 'slider_id =? ' => $id];
            $adapter->delete('video_slider_mapping', $condition);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $tagId) {
                $data[] = [
                    'video_id' => (int)$tagId,
                    self::SLIDE_ENTITY_ID => (int)$id
                ];
            }
            $adapter->insertMultiple(self::MAPPING_TABLE, $data);
        }
        if (!empty($insert) || !empty($delete)) {
            $slider->setIsChangedVideoList(true);
        }

        return $this;
    }

    /**
     * Get Posted Videos
     *
     * @param array|null $postData
     * @return array|mixed
     */
    protected function getPostedVideos(?array $postData)
    {
        $selectedVideos = [];
        if (isset($postData['video_slider_mapping'])) {
            $selectedVideos = $postData['video_slider_mapping'] = json_decode(
                $postData['video_slider_mapping'],
                true
            );
        }

        return $selectedVideos;
    }
}
