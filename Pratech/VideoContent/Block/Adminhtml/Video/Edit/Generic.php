<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Block\Adminhtml\Video\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

/**
 * Generic Button for providing url and videos id.
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
     */
    public function __construct(
        protected Context $context
    )
    {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getVideoId(): ?int
    {
        try {
            return $this->context->getRequest()->getParam('id');
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get Url
     *
     * @param string $route
     * @param array $param
     * @return string
     */
    public function getUrl(string $route = '', array $param = []): string
    {
        return $this->urlBuilder->getUrl($route, $param);
    }
}
