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

class RejectRequestButton extends GenericButton implements ButtonProviderInterface
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

        if ($this->authorization->isAllowed('Pratech_Return::request_reject') && !$request->getRejectReasonId()
            && !$request->getIsProcessed()
        ) {
            $data = [
                'label' => __('Reject Request'),
                'class' => 'process-request',
                'on_click' => 'require("uiRegistry").get("return_request_form.return_request_form.reject_modal").toggleModal();',
                'sort_order' => 20
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
    public function getRejectRequestUrl(int $requestId): string
    {
        return $this->getUrl('return/request/processrequest', ['request_id' => $requestId]);
    }
}
