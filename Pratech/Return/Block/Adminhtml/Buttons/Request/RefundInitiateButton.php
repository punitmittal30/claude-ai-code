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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Pratech\Return\Block\Adminhtml\Buttons\GenericButton;

class RefundInitiateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get Button Data.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData(): array
    {
        $data = [];
        $request = $this->requestRepositoryInterface->getById($this->getRequestId());

        $initialRefundStatusId = $this->orderReturnHelper->getInitialRefundStatusId();
        if ($this->authorization->isAllowed('Pratech_Return::refund_initiate') && $request->getIsProcessed()
            && !$request->getRejectReasonId() && $request->getInstantRefund() != 1
            && $request->getRefundStatus() == $initialRefundStatusId
        ) {
            $message = __(
                'The refund process will be initiated.'
            );

            $onClick = "confirmSetLocation('{$message}', '{$this->getRefundInitiateUrl($request->getRequestId())}')";

            $data = [
                'label' => __('Initiate Refund'),
                'class' => 'refund_initiate',
                'on_click' => $onClick,
                'sort_order' => 30
            ];
        }

        return $data;
    }

    /**
     * Credit Memos URL getter
     *
     * @param  int $requestId
     * @return string
     */
    public function getRefundInitiateUrl(int $requestId): string
    {
        return $this->getUrl('return/request/refundinitiated', ['request_id' => $requestId]);
    }
}
