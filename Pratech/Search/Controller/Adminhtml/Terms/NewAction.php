<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Controller\Adminhtml\Terms;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * NewAction controller to redirect user to Add new search terms page.
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
        Action\Context $context,
        protected ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Forward|ResponseInterface|ResultInterface
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
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_Search::pratech_search');
    }
}
