<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Block\Adminhtml\Buttons;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

class GenericButton
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param Context                    $context
     * @param OrderRepositoryInterface   $orderRepository
     * @param RequestRepositoryInterface $requestRepositoryInterface
     * @param OrderReturnHelper          $orderReturnHelper
     */
    public function __construct(
        Context                              $context,
        protected OrderRepositoryInterface   $orderRepository,
        protected RequestRepositoryInterface $requestRepositoryInterface,
        protected OrderReturnHelper $orderReturnHelper
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $context->getRequest();
        $this->authorization = $context->getAuthorization() ?: ObjectManager::getInstance()
            ->get(AuthorizationInterface::class);
    }

    /**
     * Generate url by route and parameters
     *
     * @param  string $route
     * @param  array  $params
     * @return string
     */
    public function getUrl($route = '', $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * Get Request ID.
     *
     * @return null|int
     */
    public function getRequestId(): ?int
    {
        return (int)$this->request->getParam('request_id');
    }

    /**
     * Get Order By ID.
     *
     * @param  $orderId
     * @return OrderInterface
     */
    public function getOrderById($orderId): OrderInterface
    {
        return $this->orderRepository->get($orderId);
    }
}
