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

namespace Pratech\Return\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\RequestRepositoryInterface;

class View extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Pratech_Return::request_view';

    /**
     * @var Session
     */
    private $adminSession;

    /**
     * View Return Request Constructor
     *
     * @param RequestRepositoryInterface $requestRepository
     * @param Action\Context $context
     */
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        Action\Context                     $context
    ) {
        parent::__construct($context);
        $this->adminSession = $context->getSession();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /**
         * @var Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if ($requestId = (int)$this->getRequest()->getParam('request_id')) {
            try {
                $this->requestRepository->getById($requestId);
                $backToRmaFlow = $this->adminSession->getBackToRmaFlow() ?? [];
                $backToRmaFlow = array_filter(
                    $backToRmaFlow,
                    function ($flowOrder) use ($requestId) {
                        return $flowOrder['request_id'] ?? 0 !== $requestId;
                    }
                );
                $this->adminSession->setBackToRmaFlow($backToRmaFlow);
                $resultPage->getConfig()->getTitle()->prepend(__('View Return Request'));
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This request is no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        return $resultPage;
    }
}
