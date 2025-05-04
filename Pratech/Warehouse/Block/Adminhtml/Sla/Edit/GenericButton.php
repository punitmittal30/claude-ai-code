<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Block\Adminhtml\Sla\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        protected Context $context
    ) {
    }

    /**
     * Get Sla ID.
     *
     * @return mixed|null
     */
    public function getSlaId(): mixed
    {
        try {
            return $this->context->getRequest()->getParam('sla_id');
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get URL.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
