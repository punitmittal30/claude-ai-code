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
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Banner Resource Model class
 */
class Banner extends AbstractDb
{
    /**
     * Mapping table -> pratech_banner_product
     */
    public const MAPPING_TABLE = 'pratech_banner_product';

    /**
     * Banner Entity ID constant
     */
    public const PRODUCT_ENTITY_ID = 'product_id';

    /**
     * @var string
     */
    protected $_idFieldName = 'banner_id';

    /**
     * @var string
     */
    protected $sliderBannerTable;

    /**
     * @var string
     */
    protected $bannerProductTable;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->sliderBannerTable = $this->getTable('pratech_slider_banner');
        $this->bannerProductTable = $this->getTable('pratech_banner_product');
    }

    /**
     * Get Slider ID
     *
     * @param int $bannerId
     * @return array
     */
    public function getSliderId(int $bannerId): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->sliderBannerTable, 'slider_id')
            ->where('banner_id = ?', $bannerId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Product IDs
     *
     * @param int $bannerId
     * @return array
     */
    public function getBannerProductIds(int $bannerId): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->bannerProductTable, 'product_id')
            ->where('banner_id = ?', $bannerId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Banner Position
     *
     * @param \Pratech\Banners\Model\Banner $banner
     * @return array
     */
    public function getBannerPosition(\Pratech\Banners\Model\Banner $banner)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->sliderBannerTable, 'position')
            ->where('banner_id = ?', (int)$banner->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pratech_banner', 'banner_id');
    }

    /**
     * After Save
     *
     * @param AbstractModel $object
     * @return Banner
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->_updateSliderBanner($object);
        $this->_updateBannerProducts($object);

        return parent::_afterSave($object);
    }

    /**
     * Update Banners Product.
     *
     * @param \Pratech\Banners\Model\Banner $banner
     * @return $this
     */
    protected function _updateBannerProducts(\Pratech\Banners\Model\Banner $banner): static
    {
        $banner->setIsChangedProductList(false);
        $bannerId = $banner->getId();

        $postedProducts = $banner->getPostedBannerProducts();
        $oldProductIds = $banner->getBannerProductIds($bannerId);

        $adapter = $this->getConnection();

        if (!empty($oldProductIds)) {
            $condition = ['banner_id IN(?)' => $bannerId];
            $adapter->delete($this->bannerProductTable, $condition);
        }

        if (!empty($postedProducts)) {
            $data = [];
            foreach ($postedProducts as $productId => $position) {
                $data[] = [
                    'banner_id' => (int)$bannerId,
                    'product_id' => (int)$productId,
                    'position' => $position
                ];
            }
            $adapter->insertMultiple($this->bannerProductTable, $data);
        }

        if (!empty($insert) || !empty($delete)) {
            $banner->setIsChangedProductList(true);
        }

        return $this;
    }

    /**
     * Update Slider Banner
     *
     * @param \Pratech\Banners\Model\Banner $banner
     * @return $this
     */
    protected function _updateSliderBanner(\Pratech\Banners\Model\Banner $banner)
    {
        $banner->setIsChangedSliderList(false);
        $id = $banner->getId();

        $slider = $banner->getAssignToSlider();
        $position = 1;
        $oldSlider = $banner->getSliderId($id);

        $adapter = $this->getConnection();

        if (!empty($oldSlider)) {
            $condition = ['slider_id IN(?)' => $oldSlider, 'banner_id=?' => $id];
            $adapter->delete($this->sliderBannerTable, $condition);
        }

        if (!empty($slider)) {
            $data = [
                'banner_id' => (int)$id,
                'slider_id' => (int)$slider,
                'position' => $position
            ];
            $adapter->insertMultiple($this->sliderBannerTable, $data);
        }

        if (!empty($insert) || !empty($delete)) {
            $banner->setIsChangedSliderList(true);
        }

        return $this;
    }

    /**
     * Get Products
     *
     * @param int|null $bannerId
     * @return array
     */
    public function getProducts(?int $bannerId): array
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, self::PRODUCT_ENTITY_ID)
            ->where('banner_id = ?', (int)$bannerId);

        return $adapter->fetchCol($select);
    }

    /**
     * Get Products
     *
     * @param int|null $bannerId
     * @return array
     */
    public function getProductsAndPosition(?int $bannerId): array
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::MAPPING_TABLE, [self::PRODUCT_ENTITY_ID, 'position'])
            ->where('banner_id = ?', (int)$bannerId)->order('position asc');

        return $adapter->fetchPairs($select);
    }
}
