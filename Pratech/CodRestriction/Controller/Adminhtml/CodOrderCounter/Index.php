<?php
/**
 * Pratech_CodRestriction
 *
 * @category  XML
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\CodRestriction\Controller\Adminhtml\CodOrderCounter;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;

class Index extends Action
{
    /**
     * Index constructor.
     *
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context               $context,
        protected PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Grid Page
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Pratech_CodRestriction::cod_order_counter');
        $resultPage->getConfig()->getTitle()->prepend(__('COD Order Counters'));
        return $resultPage;
    }

    /**
     * ACL check
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_CodRestriction::cod_order_counter');
    }
}
