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

namespace Pratech\Quiz\Controller\Adminhtml\QuizQuestion;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Pratech\Quiz\Model\QuizQuestionFactory;

class Delete extends Action
{
    /**
     * Delete Quiz Question Constructor
     *
     * @param Action\Context      $context
     * @param QuizQuestionFactory $quizQuestionFactory
     */
    public function __construct(
        Action\Context $context,
        protected QuizQuestionFactory $quizQuestionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Delete Quiz Question
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('question_id');
        /**
        * @var Redirect $resultRedirect
        */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $quizQuestion = $this->quizQuestionFactory->create()->load($id);
                $quizQuestion->delete();
                $this->messageManager->addSuccessMessage(__('The quiz question has been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while deleting the quiz question.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a quiz question to delete.'));
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
        return $this->_authorization->isAllowed('Pratech_Quiz::quiz_question');
    }
}
