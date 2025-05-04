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

namespace Pratech\Banners\Api\Data;

/**
 * Interface BannerItemInterface
 *
 * @api
 */
interface BannerItemInterface
{
    public const BANNER_ID = 'banner_id';

    public const NAME = 'name';

    public const URL = 'url';

    public const ACTION_URL = 'action_url';

    public const TITLE = 'title';

    public const DESCRIPTION = 'description';

    /**
     * GetBannerId
     *
     * @return int
     */
    public function getBannerId();

    /**
     * GetName
     *
     * @return string
     */
    public function getName();

    /**
     * GetUrl
     *
     * @return string
     */
    public function getUrl();

    /**
     * GetActionUrl
     *
     * @return string
     */
    public function getActionUrl();

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription();

    /**
     * SetBannerId
     *
     * @param int $bannerId
     * @return $this
     */
    public function setBannerId(int $bannerId);

    /**
     * SetName
     *
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name);

    /**
     * SetUrl
     *
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url);

    /**
     * SetActionUrl
     *
     * @param string|null $actionUrl
     * @return $this
     */
    public function setActionUrl(?string $actionUrl);

    /**
     * SetTitle
     *
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title);

    /**
     * Set Description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description);
}
