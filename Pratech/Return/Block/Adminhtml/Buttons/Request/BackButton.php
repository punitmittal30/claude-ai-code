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

namespace Pratech\Return\Block\Adminhtml\Buttons\Request;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Block\Adminhtml\Buttons\GenericButton;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context                    $context
     * @param RedirectInterface          $redirect
     * @param OrderRepositoryInterface   $orderRepository
     * @param RequestRepositoryInterface $requestRepositoryInterface
     * @param OrderReturnHelper          $orderReturnHelper
     */
    public function __construct(
        Context                              $context,
        protected RedirectInterface          $redirect,
        protected OrderRepositoryInterface   $orderRepository,
        protected RequestRepositoryInterface $requestRepositoryInterface,
        protected OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context, $orderRepository, $requestRepositoryInterface, $orderReturnHelper);
        $this->session = $context->getBackendSession();
    }

    /**
     * Get Button Data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $onClick = sprintf("location.href = '%s'", $this->getBackUrl());
        $data = [
            'label' => __('Back'),
            'class' => 'action- scalable back',
            'id' => 'back',
            'on_click' => $onClick,
            'sort_order' => 20,
        ];

        return $data;
    }

    /**
     * Get Back URL.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        $returnUrl = $this->session->getreturnReturnUrl();

        if (!$returnUrl) {
            $returnUrl = $this->redirect->getRefererUrl();
            $this->session->setreturnReturnUrl($returnUrl);
        }

        return $returnUrl;
    }
}
