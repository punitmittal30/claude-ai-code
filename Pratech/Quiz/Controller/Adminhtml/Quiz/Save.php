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
use Magento\Framework\Controller\Result\Redirect;
use Pratech\Quiz\Model\QuizFactory;

class Save extends Action
{

    /**
     * Save Quiz Constructor
     *
     * @param Action\Context $context
     * @param QuizFactory    $quizFactory
     */
    public function __construct(
        Action\Context $context,
        protected QuizFactory $quizFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * Save Quiz Data
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /**
         * @var Redirect $resultRedirect
        */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $id = $this->getRequest()->getParam('quiz_id');
            try {
                $quiz = $this->quizFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['quiz_id']);
                }
                $quiz->setData($data);
                $quiz->save();

                $this->messageManager->addSuccessMessage(__('The quiz has been saved.'));

                if (!$this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while saving the quiz.'));
            }
        }

        return $resultRedirect->setPath('*/*/edit', ['id' => $quiz->getQuizId()]);
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
