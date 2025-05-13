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
 * Interface BannerResponseItemInterface
 *
 * @api
 */
interface BannerPlatformItemInterface
{
    public const WEB = 'web';

    public const M_WEB = 'mWeb';

    public const APP = 'app';

    /**
     * GetWeb
     *
     * @return \Pratech\Banners\Api\Data\BannerItemInterface[]
     */
    public function getWeb();

    /**
     * GetMWeb
     *
     * @return \Pratech\Banners\Api\Data\BannerItemInterface[]
     */
    public function getMWeb();

    /**
     * GetApp
     *
     * @return \Pratech\Banners\Api\Data\BannerItemInterface[]
     */
    public function getApp();

    /**
     * SetWeb
     *
     * @param \Pratech\Banners\Api\Data\BannerItemInterface[] $web
     * @return $this
     */
    public function setWeb(array $web);

    /**
     * SetMWeb
     *
     * @param \Pratech\Banners\Api\Data\BannerItemInterface[] $mWeb
     * @return $this
     */
    public function setMWeb(array $mWeb);

    /**
     * SetApp
     *
     * @param \Pratech\Banners\Api\Data\BannerItemInterface[] $app
     * @return $this
     */
    public function setApp(array $app);
}
