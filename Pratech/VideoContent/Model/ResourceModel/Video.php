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
     * Mapping table -> video_product_mapping
     */
    public const MAPPING_TABLE = 'video_product_mapping';

    /**
     * Product ID constant
     */
    public const PRODUCT_ENTITY_ID = 'product_id';

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
     * @var string
     */
    protected $videoProductTable;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->sliderVideoMapping = $this->getTable('video_slider_mapping');
        $this->videoProductTable = $this->getTable('video_product_mapping');
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
     * Get Product IDs
     *
     * @param int $videoId
     * @return array
     */
    public function getVideoProductIds(int $videoId): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->videoProductTable, 'product_id')
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
        $this->_updateVideoProducts($object);

        return parent::_afterSave($object);
    }

    /**
     * Update Videos Product.
     *
     * @param \Pratech\VideoContent\Model\Video $video
     * @return $this
     */
    protected function _updateVideoProducts(\Pratech\VideoContent\Model\Video $video): static
    {
        $video->setIsChangedProductList(false);
        $videoId = $video->getId();

        $postedProducts = $video->getPostedVideoProducts();
        $oldProductIds = $video->getVideoProductIds($videoId);

        $adapter = $this->getConnection();

        if (!empty($oldProductIds)) {
            $condition = ['video_id IN(?)' => $videoId];
            $adapter->delete($this->videoProductTable, $condition);
        }

        if (!empty($postedProducts)) {
            $data = [];
            foreach ($postedProducts as $productId => $position) {
                $data[] = [
                    'video_id' => (int)$videoId,
                    'product_id' => (int)$productId,
                    'position' => $position
                ];
            }
            $adapter->insertMultiple($this->videoProductTable, $data);
        }

        if (!empty($insert) || !empty($delete)) {
            $video->setIsChangedProductList(true);
        }

        return $this;
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

    /**
     * Get Products
     *
     * @param int|null $videoId
     * @return array
     */
    public function getProducts(?int $videoId): array
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::PRODUCT_ENTITY_ID)
            ->where('video_id = ?', (int)$videoId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Products
     *
     * @param int|null $videoId
     * @return array
     */
    public function getProductsAndPosition(?int $videoId): array
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, [self::PRODUCT_ENTITY_ID, 'position'])
            ->where('video_id = ?', (int)$videoId)->order('position asc');

        return $adapter->fetchPairs($select);
    }
}
