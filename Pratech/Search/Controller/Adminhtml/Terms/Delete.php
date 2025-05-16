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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Search\Model\SearchTermsFactory;

/**
 * Delete controller to delete a search term.
 */
class Delete extends Action
{

    /**
     * NewAction constructor
     *
     * @param Action\Context     $context
     * @param SearchTermsFactory $searchTermsFactory
     * @param RedirectFactory    $redirectFactory
     */
    public function __construct(
        protected Action\Context  $context,
        protected SearchTermsFactory          $searchTermsFactory,
        protected RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $searchTerm = $this->searchTermsFactory->create()->load($id);
            try {
                $searchTerm->delete();
                $this->_eventManager->dispatch('search_term_controller_delete_after', ['searchTerm' => $searchTerm]);
                $this->messageManager->addSuccessMessage(__('Search Term has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirectFactory->create()->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
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
