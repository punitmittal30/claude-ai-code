<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\DiscountReport\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{

    /**
     * Construct Method
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        protected Context $context,
        protected PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->messageManager->addNotice(
            __('Select the order date range.')
        );

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Pratech_DiscountReport::order_report');
        $resultPage->getConfig()->getTitle()->prepend(__("Orders Discount Report"));
        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_DiscountReport::order_report');
    }
}
