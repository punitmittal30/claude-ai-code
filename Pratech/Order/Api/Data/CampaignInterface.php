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

namespace Pratech\Order\Api\Data;

/**
 * Order Campaign Interface
 *
 * @api
 */
interface CampaignInterface
{

    public const IP_ADDRESS = 'ip_address';

    public const PLATFORM = 'platform';

    public const UTM_ID = 'utm_id';

    public const UTM_SOURCE = 'utm_source';

    public const UTM_CAMPAIGN = 'utm_campaign';

    public const UTM_MEDIUM = 'utm_medium';

    public const UTM_TERM = 'utm_term';

    public const UTM_CONTENT = 'utm_content';

    public const TRACKER_COOKIE = 'tracker_cookie';

    public const UTM_TIMESTAMP = 'utm_timestamp';

    /**
     * Get IP Address
     *
     * @return string|null
     */
    public function getIpAddress();

    /**
     * Set IP Address
     *
     * @param string|null $ipAddress
     * @return $this
     */
    public function setIpAddress(?string $ipAddress);

    /**
     * Get platform code
     *
     * @return int
     */
    public function getPlatform();

    /**
     * Set platform code
     *
     * @param int $platform
     * @return $this
     */
    public function setPlatform(int $platform);

    /**
     * Get Utm Source
     *
     * @return string|null
     */
    public function getUtmId();

    /**
     * Set Utm Source
     *
     * @param string|null $utmId
     * @return $this
     */
    public function setUtmId(?string $utmId);

    /**
     * Get Utm Source
     *
     * @return string|null
     */
    public function getUtmSource();

    /**
     * Set Utm Source
     *
     * @param string|null $utmSource
     * @return $this
     */
    public function setUtmSource(?string $utmSource);

    /**
     * Get Utm Campaign
     *
     * @return string|null
     */
    public function getUtmCampaign();

    /**
     * Set Utm Campaign
     *
     * @param string|null $utmCampaign
     * @return $this
     */
    public function setUtmCampaign(?string $utmCampaign);

    /**
     * Get Utm Source
     *
     * @return string|null
     */
    public function getUtmMedium();

    /**
     * Set Utm Medium
     *
     * @param string|null $utmMedium
     * @return $this
     */
    public function setUtmMedium(?string $utmMedium);

    /**
     * Get Utm Term
     *
     * @return string|null
     */
    public function getUtmTerm();

    /**
     * Set Utm Term
     *
     * @param string|null $utmTerm
     * @return $this
     */
    public function setUtmTerm(?string $utmTerm);

    /**
     * Get Utm Content
     *
     * @return string|null
     */
    public function getUtmContent();

    /**
     * Set Utm Content
     *
     * @param string|null $utmContent
     * @return $this
     */
    public function setUtmContent(?string $utmContent);

    /**
     * Get Tracker Cookie
     *
     * @return string|null
     */
    public function getTrackerCookie();

    /**
     * Set Tracker Cookie
     *
     * @param string|null $trackerCookie
     * @return $this
     */
    public function setTrackerCookie(?string $trackerCookie);

    /**
     * Get Utm Timestamp
     *
     * @return string|null
     */
    public function getUtmTimestamp();

    /**
     * Set Utm Timestamp
     *
     * @param string|null $utmTimestamp
     * @return $this
     */
    public function setUtmTimestamp(?string $utmTimestamp);
}
