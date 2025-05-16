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
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Pratech\Quiz\Model\QuizQuestionFactory;

class Edit extends Action
{
    /**
     * Quiz Question Edit Constructor
     *
     * @param Action\Context      $context
     * @param PageFactory         $resultPageFactory
     * @param QuizQuestionFactory $quizQuestionFactory
     */
    public function __construct(
        Action\Context $context,
        protected PageFactory $resultPageFactory,
        protected QuizQuestionFactory $quizQuestionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Page Method.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $id = $this->getRequest()->getParam('question_id');
        $quizQuestion = $this->quizQuestionFactory->create();

        if ($id) {
            $quizQuestion->load($id);
            if (!$quizQuestion->getId()) {
                $this->messageManager->addErrorMessage(__('This quiz question no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->_getSession()->setQuizData($quizQuestion->getData());
        /**
         * @var \Magento\Framework\View\Result\Page $resultPage
        */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Pratech_Quiz::quiz_question');
        $resultPage->getConfig()->getTitle()->prepend($id ? __('Edit Quiz Question') : __('New Quiz Question'));

        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Quiz::quiz_question');
    }
}
