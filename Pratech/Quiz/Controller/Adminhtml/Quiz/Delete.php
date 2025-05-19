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

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Quiz\Model\QuizFactory;

class Delete extends Action
{
    /**
     * Quiz Delete Constructor
     *
     * @param Action\Context $context
     * @param QuizFactory $quizFactory
     */
    public function __construct(
        Action\Context        $context,
        protected QuizFactory $quizFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Delete quiz record
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $quiz = $this->quizFactory->create()->load($id);
                $quiz->delete();
                $this->messageManager->addSuccessMessage(__('The quiz has been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while deleting the quiz.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a quiz to delete.'));
        }
        return $resultRedirect->setPath('*/*/');
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
