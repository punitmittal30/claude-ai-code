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

namespace Pratech\Filters\Controller\Adminhtml\Filters;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Filters\Model\FiltersPositionFactory;

/**
 * Delete controller to delete a filters position.
 */
class Delete extends Action
{

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param FiltersPositionFactory $filtersPositionFactory
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        protected Action\Context         $context,
        protected FiltersPositionFactory $filtersPositionFactory,
        protected RedirectFactory        $redirectFactory
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
            $filtersPosition = $this->filtersPositionFactory->create()->load($id);
            try {
                $filtersPosition->delete();
                $this->_eventManager->dispatch(
                    'filters_position_controller_delete_after',
                    ['filtersPosition' => $filtersPosition]
                );
                $this->messageManager->addSuccessMessage(
                    __('Filters Position has been successfully removed')
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
        return $this->_authorization->isAllowed('Pratech_Filters::filters_position');
    }
}
