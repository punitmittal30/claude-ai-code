<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\Banners\Model\Slider as SliderModel;

/**
 * Slider Resource Model class
 */
class Slider extends AbstractDb
{

    /**
     * Mapping table -> pratech_slider_banner
     */
    public const MAPPING_TABLE = 'pratech_slider_banner';

    /**
     * Slide Entity ID constant
     */
    public const SLIDE_ENTITY_ID = 'slider_id';

    /**
     * Banner Entity ID constant
     */
    public const BANNER_ENTITY_ID = 'banner_id';

    /**
     * Get Banners
     *
     * @param int|null $sliderId
     * @return array
     */
    public function getBanners(?int $sliderId)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::BANNER_ENTITY_ID)
            ->where('slider_id = ?', (int)$sliderId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Banners Not Assigned
     *
     * @return array
     */
    public function getBannersNotAssigned()
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::BANNER_ENTITY_ID)
            ->distinct(self::BANNER_ENTITY_ID);
        $bannerDistinct = $adapter->fetchCol($select);

        $select = $adapter->select()
            ->from('pratech_banner', self::BANNER_ENTITY_ID);
        $bannerAll = $adapter->fetchCol($select);

        return array_diff($bannerAll, $bannerDistinct);
    }

    /**
     * Get Banner IDs
     *
     * @param SliderModel $slider
     * @return array
     */
    public function getBannersIds(SliderModel $slider)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::BANNER_ENTITY_ID)
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
        $this->_init('pratech_slider', 'slider_id');
    }

    /**
     * After Save
     *
     * @param AbstractModel $object
     * @return Slider
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->_updateSliderBanner($object);
        return parent::_afterSave($object);
    }

    /**
     * Update Slider Banner
     *
     * @param SliderModel $slider
     * @return $this
     */
    protected function _updateSliderBanner(SliderModel $slider)
    {
        $id = $slider->getSliderId();
        $banners = $this->getPostedBanners($slider->getPostedData());
        $oldBanners = $slider->getBannersIds() ? $slider->getBannersIds() : [];

        if (empty($banners) && !isset($slider->getPostedData()['pratech_slider_banner'])) {
            return $this;
        }

        $insert = array_diff($banners, $oldBanners);
        $delete = array_diff($oldBanners, $banners);
        $adapter = $this->getConnection();

        if (!empty($delete)) {
            $condition = ['banner_id IN(?)' => $delete, 'slider_id =? ' => $id];
            $adapter->delete('pratech_slider_banner', $condition);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $tagId) {
                $data[] = [
                    'banner_id' => (int)$tagId,
                    self::SLIDE_ENTITY_ID => (int)$id,
                    'position' => 1
                ];
            }
            $adapter->insertMultiple(self::MAPPING_TABLE, $data);
        }
        if (!empty($insert) || !empty($delete)) {
            $slider->setIsChangedBannerList(true);
        }

        return $this;
    }

    /**
     * Get Posted Banners
     *
     * @param array|null $postData
     * @return array|mixed
     */
    protected function getPostedBanners(?array $postData)
    {
        $selectedBanners = [];
        if (isset($postData['pratech_slider_banner'])) {
            $selectedBanners = $postData['pratech_slider_banner'] = json_decode(
                $postData['pratech_slider_banner'],
                true
            );
        }

        return $selectedBanners;
    }
}
