<?php

namespace Pratech\Banners\Api\Data;

interface BannerResponseInterface
{
    public const STATUS = 'status';

    public const MESSAGE = 'message';

    public const RESOURCE = 'resource';

    public const DATA = 'data';

    /**
     * Get Status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set Message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message);

    /**
     * Get Resource
     *
     * @return string
     */
    public function getResource();

    /**
     * Set Resource
     *
     * @param string $resource
     * @return $this
     */
    public function setResource(string $resource);

    /**
     * Get Data
     *
     * @return \Pratech\Banners\Api\Data\SliderItemInterface
     */
    public function getData();

    /**
     * Set Data
     *
     * @param \Pratech\Banners\Api\Data\SliderItemInterface $slider
     * @return $this
     */
    public function setData(\Pratech\Banners\Api\Data\SliderItemInterface $slider);
}
