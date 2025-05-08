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

namespace Pratech\Banners\Model;

use Magento\Framework\Model\AbstractModel;

class Banner extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'banner';

    /**
     * Get Slider ID
     *
     * @param int $bannerId
     * @return array
     */
    public function getSliderId(int $bannerId)
    {
        if (!$this->hasData('slider_id')) {
            $ids = $this->getResource()->getSliderId($bannerId);

            $this->setData('slider_id', $ids);
        }

        return (array)$this->getData('slider_id');
    }

    /**
     * Get Product IDs
     *
     * @param int $bannerId
     * @return array
     */
    public function getBannerProductIds(int $bannerId)
    {
        if (!$this->hasData('product_id')) {
            $ids = $this->getResource()->getBannerProductIds($bannerId);

            $this->setData('product_id', $ids);
        }

        return (array)$this->getData('product_id');
    }

    /**
     * Get Banner Position
     *
     * @return array
     */
    public function getBannerPosition()
    {
        if (!$this->hasData('position')) {
            $ids = $this->getResource()->getBannerPosition($this);

            $this->setData('position', $ids);
        }

        return (array)$this->getData('position');
    }

    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Banner::class);
    }
}
