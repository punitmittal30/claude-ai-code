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
 * Interface SliderItemInterface
 *
 * @api
 */
interface SliderItemInterface
{
    public const SLIDER_ID = 'slider_id';

    public const NAME = 'name';

    public const START_DATE = 'start_date';

    public const END_DATE = 'end_date';

    public const BANNERS = 'banners';

    /**
     * GetSliderId
     *
     * @return int
     */
    public function getSliderId();

    /**
     * GetName
     *
     * @return string
     */
    public function getName();

    /**
     * GetStartDate
     *
     * @return string|null
     */
    public function getStartDate();

    /**
     * GetEndDate
     *
     * @return string|null
     */
    public function getEndDate();

    /**
     * Get Banners for the Slider.
     *
     * @return \Pratech\Banners\Api\Data\BannerPlatformItemInterface $banners
     */
    public function getBanners();

    // optional setters:

    /**
     * SetSliderId
     *
     * @param int $sliderId
     * @return $this
     */
    public function setSliderId(int $sliderId);

    /**
     * SetName
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * SetStartDate
     *
     * @param string|null $startDate
     * @return $this
     */
    public function setStartDate(?string $startDate);

    /**
     * SetEndDate
     *
     * @param string|null $endDate
     * @return $this
     */
    public function setEndDate(?string $endDate);

    /**
     * Set Banners for the Slider.
     *
     * @param \Pratech\Banners\Api\Data\BannerPlatformItemInterface $banners
     * @return $this
     */
    public function setBanners(\Pratech\Banners\Api\Data\BannerPlatformItemInterface $banners);
}
