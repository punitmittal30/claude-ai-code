<?php

namespace Pratech\Banners\Model\Data;

use Pratech\Banners\Api\Data\BannerResponseInterface;

class BannerResponse extends \Pratech\Base\Model\ResponseDataObject implements BannerResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status)
    {
        return $this->_setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->_getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message)
    {
        return $this->_setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getResource()
    {
        return $this->_getData(self::RESOURCE);
    }

    /**
     * @inheritDoc
     */
    public function setResource(string $resource)
    {
        return $this->_setData(self::RESOURCE, $resource);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->_getData(self::DATA);
    }

    /**
     * @inheritDoc
     */
    public function setData($slider)
    {
        return $this->_setData(self::DATA, $slider);
    }
}
