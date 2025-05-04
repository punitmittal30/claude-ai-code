<?php

namespace Pratech\Banners\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Banners\Api\Data\SliderItemInterface;

class SliderItem extends DataObject implements SliderItemInterface
{
    /**
     * @inheritDoc
     */
    public function getSliderId()
    {
        return $this->_getData(self::SLIDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function getStartDate()
    {
        return $this->_getData(self::START_DATE);
    }

    /**
     * @inheritDoc
     */
    public function getEndDate()
    {
        return $this->_getData(self::END_DATE);
    }

    /**
     * @inheritDoc
     */
    public function getBanners()
    {
        return $this->_getData(self::BANNERS);
    }

    /**
     * @inheritDoc
     */
    public function setSliderId(int $sliderId)
    {
        return $this->setData(self::SLIDER_ID, $sliderId);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function setStartDate(string|null $startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * @inheritDoc
     */
    public function setEndDate(string|null $endDate)
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    /**
     * @inheritDoc
     */
    public function setBanners(\Pratech\Banners\Api\Data\BannerPlatformItemInterface $banners)
    {
        return $this->setData(self::BANNERS, $banners);
    }
}
