<?php
/**
 * Pratech_Quiz
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Quiz
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Quiz\Controller\Adminhtml\Quiz;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Quiz\Model\ResourceModel\Quiz\CollectionFactory;

class MassDelete extends Action
{
    /**
     * Mass Delete Quiz Constructor
     *
     * @param Action\Context    $context
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Action\Context $context,
        protected CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Mass Delete Quiz Records
     */
    public function execute()
    {
        $selectedIds = $this->getRequest()->getParam('selected');
        if (!is_array($selectedIds) || empty($selectedIds)) {
            $this->messageManager->addErrorMessage(__('Please select quizzes to delete.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('quiz_id', ['in' => $selectedIds]);

            foreach ($collection as $quiz) {
                $quiz->delete();
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 quiz(es) have been deleted.', count($selectedIds))
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the quizzes.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_Quiz::manage_quiz');
    }
}
