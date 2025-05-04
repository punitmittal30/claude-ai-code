<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Block\Adminhtml\Slider\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Generic Button for providing url and slider id.
 */
class Generic
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Generic constructor
     *
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        protected Registry $registry
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        $slideData = $this->registry->registry('video_slider');
        return $slideData ? $slideData->getId() : null;
    }

    /**
     * Get URL
     *
     * @param string $route
     * @param array $param
     * @return string
     */
    public function getUrl($route = '', $param = []): string
    {
        return $this->urlBuilder->getUrl($route, $param);
    }
}
