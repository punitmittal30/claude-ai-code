<?php

namespace Pratech\Banners\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Banners\Api\Data\BannerPlatformItemInterface;

class BannerPlatformItem extends DataObject implements BannerPlatformItemInterface
{
    /**
     * @inheritDoc
     */
    public function getWeb()
    {
        return $this->_getData(self::WEB);
    }

    /**
     * @inheritDoc
     */
    public function getMWeb()
    {
        return $this->_getData(self::M_WEB);
    }

    /**
     * @inheritDoc
     */
    public function getApp()
    {
        return $this->_getData(self::APP);
    }

    /**
     * @inheritDoc
     */
    public function setWeb(array $web)
    {
        return $this->setData(self::WEB, $web);
    }

    /**
     * @inheritDoc
     */
    public function setMWeb(array $mWeb)
    {
        return $this->setData(self::M_WEB, $mWeb);
    }

    /**
     * @inheritDoc
     */
    public function setApp(array $app)
    {
        return $this->setData(self::APP, $app);
    }
}
