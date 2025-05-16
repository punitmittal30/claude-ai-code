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

namespace Pratech\Banners\Block\Adminhtml\Banner\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Generic Button for providing url and banner id.
 */
class Generic
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Generic constructor
     *
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context  $context,
        Registry $registry
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry = $registry;
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        $bannerData = $this->registry->registry('banner');
        return $bannerData ? $bannerData->getId() : null;
    }

    /**
     * Get Url
     *
     * @param string $route
     * @param array $param
     * @return string
     */
    public function getUrl($route = '', $param = [])
    {
        return $this->urlBuilder->getUrl($route, $param);
    }
}
