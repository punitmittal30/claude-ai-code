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

namespace Pratech\Return\Block\Adminhtml\Form;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Pratech\Return\Model\OptionSource\RejectReasons as RejectReasonOptions;

class Reject extends Template
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $_template = 'Form/reject.phtml';

    /**
     * @param Context             $context
     * @param UrlInterface        $urlBuilder
     * @param RejectReasonOptions $rejectReasonOptions
     * @param array               $data
     */
    public function __construct(
        Context                     $context,
        private UrlInterface        $urlBuilder,
        private RejectReasonOptions $rejectReasonOptions,
        array                       $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $context->getRequest();
    }

    /**
     * Get Action Url.
     *
     * @return string
     */
    public function getActionUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'return/request/rejectrequest',
            ['request_id' => $this->getRequestId()]
        );
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
     * Get Reject Reasons List.
     *
     * @return array
     */
    public function getRejectReasonsList(): array
    {
        return $this->rejectReasonOptions->toOptionArray();
    }
}
