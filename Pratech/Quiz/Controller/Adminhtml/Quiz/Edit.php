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
use Magento\Framework\View\Result\PageFactory;
use Pratech\Quiz\Model\QuizFactory;

class Edit extends Action
{

    /**
     * Edit Quiz Constructor
     *
     * @param Action\Context $context
     * @param PageFactory    $resultPageFactory
     * @param QuizFactory    $quizFactory
     */
    public function __construct(
        Action\Context $context,
        protected PageFactory $resultPageFactory,
        protected QuizFactory $quizFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $quiz = $this->quizFactory->create();

        if ($id) {
            $quiz->load($id);
            if (!$quiz->getId()) {
                $this->messageManager->addErrorMessage(__('This quiz no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->_getSession()->setQuizData($quiz->getData());

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Pratech_Quiz::quiz');
        $resultPage->getConfig()->getTitle()->prepend($id ? __('Edit Quiz') : __('New Quiz'));

        return $resultPage;
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
