<?php

namespace Pratech\Banners\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Banners\Api\Data\BannerItemInterface;

class BannerItem extends DataObject implements BannerItemInterface
{
    /**
     * @inheritDoc
     */
    public function getBannerId()
    {
        return $this->_getData(self::BANNER_ID);
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
    public function getUrl()
    {
        return $this->_getData(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function getActionUrl()
    {
        return $this->_getData(self::ACTION_URL);
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->_getData(self::TITLE);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->_getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setBannerId(int $bannerId)
    {
        return $this->setData(self::BANNER_ID, $bannerId);
    }

    /**
     * @inheritDoc
     */
    public function setName(?string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function setUrl(?string $url)
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function setActionUrl(?string $actionUrl)
    {
        return $this->setData(self::ACTION_URL, $actionUrl);
    }

    /**
     * @inheritDoc
     */
    public function setTitle(?string $title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }
}
