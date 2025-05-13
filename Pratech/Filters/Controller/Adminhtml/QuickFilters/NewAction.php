<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Controller\Adminhtml\QuickFilters;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * NewAction controller to redirect user to Add new Quick Filters page.
 */
class NewAction extends Action
{
    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        Action\Context           $context,
        protected ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultForward = $this->forwardFactory->create();
        return $resultForward->forward('edit');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Filters::quick_filter');
    }
}
