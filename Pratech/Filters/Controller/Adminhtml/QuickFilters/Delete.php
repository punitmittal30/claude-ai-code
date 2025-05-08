<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Controller\Adminhtml\QuickFilters;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Filters\Model\QuickFiltersFactory;

/**
 * Delete controller to delete a Quick Filters.
 */
class Delete extends Action
{

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param QuickFiltersFactory $quickFiltersFactory
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        protected Action\Context      $context,
        protected QuickFiltersFactory $quickFiltersFactory,
        protected RedirectFactory     $redirectFactory
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
            $quickFilters = $this->quickFiltersFactory->create()->load($id);
            try {
                $quickFilters->delete();
                $this->_eventManager->dispatch(
                    'quick_filter_controller_delete_after',
                    ['quickFilters' => $quickFilters]
                );
                $this->messageManager->addSuccessMessage(
                    __('Quick Filters has been successfully removed')
                );
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirectFactory->create()
                    ->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
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
