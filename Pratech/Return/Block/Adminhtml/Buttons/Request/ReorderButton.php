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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Block\Adminhtml\Buttons\GenericButton;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

class ReorderButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param Context                    $context
     * @param Reorder                    $reorderHelper
     * @param OrderRepositoryInterface   $orderRepository
     * @param RequestRepositoryInterface $requestRepository
     * @param OrderReturnHelper          $orderReturnHelper
     */
    public function __construct(
        Context                              $context,
        protected Reorder                    $reorderHelper,
        protected OrderRepositoryInterface   $orderRepository,
        protected RequestRepositoryInterface $requestRepository,
        protected OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context, $orderRepository, $requestRepository, $orderReturnHelper);
    }

    /**
     * Get Button Data.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData(): array
    {
        $data = [];
        $request = $this->requestRepository->getById($this->getRequestId());
        $order = $this->getOrderById($request->getOrderId());

        if ($this->authorization->isAllowed('Magento_Sales::reorder')
            && $order->canReorderIgnoreSalable()
        ) {
            $onClick = sprintf("location.href = '%s'", $this->getReorderUrl($order->getEntityId()));
            $data = [
                'label' => __('New Order'),
                'class' => 'reorder',
                'on_click' => $onClick,
                'sort_order' => 20
            ];
        }

        return $data;
    }

    /**
     * Reorder URL getter
     *
     * @param int $orderId
     *
     * @return string
     */
    public function getReorderUrl(int $orderId): string
    {
        return $this->getUrl(
            'sales/order_create/reorder',
            [
                'order_id' => $orderId,
                'rma_request_id' => $this->getRequestId()
            ]
        );
    }
}
