<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Model\Data;

use Pratech\Order\Api\Data\CampaignInterface;
use Magento\Framework\DataObject;

/**
 * Class Campaign to get request data
 */
class Campaign extends DataObject implements CampaignInterface
{
    /**
     * @inheritDoc
     */
    public function getIpAddress()
    {
        return $this->_getData(self::IP_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setIpAddress(?string $ipAddress)
    {
        return $this->setData(self::IP_ADDRESS, $ipAddress);
    }

    /**
     * @inheritDoc
     */
    public function getPlatform()
    {
        return $this->_getData(self::PLATFORM);
    }

    /**
     * @inheritDoc
     */
    public function setPlatform(int $platform)
    {
        return $this->setData(self::PLATFORM, $platform);
    }

    /**
     * @inheritDoc
     */
    public function getUtmSource()
    {
        return $this->_getData(self::UTM_SOURCE);
    }

    /**
     * @inheritDoc
     */
    public function setUtmSource(?string $utmSource)
    {
        return $this->setData(self::UTM_SOURCE, $utmSource);
    }

    /**
     * @inheritDoc
     */
    public function getUtmId()
    {
        return $this->_getData(self::UTM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUtmId(?string $utmId)
    {
        return $this->setData(self::UTM_ID, $utmId);
    }

    /**
     * @inheritDoc
     */
    public function getUtmCampaign()
    {
        return $this->_getData(self::UTM_CAMPAIGN);
    }

    /**
     * @inheritDoc
     */
    public function setUtmCampaign(?string $utmCampaign)
    {
        return $this->setData(self::UTM_CAMPAIGN, $utmCampaign);
    }

    /**
     * @inheritDoc
     */
    public function getUtmMedium()
    {
        return $this->_getData(self::UTM_MEDIUM);
    }

    /**
     * @inheritDoc
     */
    public function setUtmMedium(?string $utmMedium)
    {
        return $this->setData(self::UTM_MEDIUM, $utmMedium);
    }

    /**
     * @inheritDoc
     */
    public function getUtmTerm()
    {
        return $this->_getData(self::UTM_TERM);
    }

    /**
     * @inheritDoc
     */
    public function setUtmTerm(?string $utmTerm)
    {
        return $this->setData(self::UTM_TERM, $utmTerm);
    }

    /**
     * @inheritDoc
     */
    public function getUtmContent()
    {
        return $this->_getData(self::UTM_CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setUtmContent(?string $utmContent)
    {
        return $this->setData(self::UTM_CONTENT, $utmContent);
    }

    /**
     * @inheritDoc
     */
    public function getTrackerCookie()
    {
        return $this->_getData(self::TRACKER_COOKIE);
    }

    /**
     * @inheritDoc
     */
    public function setTrackerCookie(?string $trackerCookie)
    {
        return $this->setData(self::TRACKER_COOKIE, $trackerCookie);
    }

    /**
     * @inheritDoc
     */
    public function getUtmTimestamp()
    {
        return $this->_getData(self::UTM_TIMESTAMP);
    }

    /**
     * @inheritDoc
     */
    public function setUtmTimestamp(?string $utmTimestamp)
    {
        return $this->setData(self::UTM_TIMESTAMP, $utmTimestamp);
    }
}
