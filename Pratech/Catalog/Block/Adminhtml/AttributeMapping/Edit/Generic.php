<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Block\Adminhtml\AttributeMapping\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Generic Button for providing url and mapping id.
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
     * @param Context  $context
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
        $mappingData = $this->registry->registry('attribute_mapping');
        return $mappingData ? $mappingData->getId() : null;
    }

    /**
     * Get Url
     *
     * @param  string $route
     * @param  array  $param
     * @return string
     */
    public function getUrl($route = '', $param = [])
    {
        return $this->urlBuilder->getUrl($route, $param);
    }
}
