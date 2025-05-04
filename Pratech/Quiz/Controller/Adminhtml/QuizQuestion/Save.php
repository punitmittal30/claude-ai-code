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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Quiz\Model\QuizAnswerFactory;
use Pratech\Quiz\Model\QuizQuestionFactory;

class Save extends Action
{
    /**
     * Save Quiz Question Constructor
     *
     * @param Context $context
     * @param QuizQuestionFactory $questionFactory
     * @param QuizAnswerFactory $answerFactory
     */
    public function __construct(
        Context                       $context,
        protected QuizQuestionFactory $questionFactory,
        protected QuizAnswerFactory   $answerFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$data) {
            return $this->_redirect('*/*/');
        }
        try {
            // Save question data
            $questionId = $data['question_id'] ?? null;
            $question = $this->questionFactory->create();
            if ($questionId) {
                $question->load($questionId);
                if (!$question->getId()) {
                    throw new LocalizedException(__('Invalid question ID.'));
                }
            }

            $question->setData('question_text', $data['question_text']);
            $question->setData('question_type', $data['question_type']);
            $question->setData('quiz_id', $data['quiz_id']);
            $question->save();

            // Save answers data
            $questionId = $question->getId();
            if (isset($data['answers_data']) && is_array($data['answers_data'])) {
                $receivedAnswerIds = [];
                foreach ($data['answers_data'] as $answerData) {
                    $answer = $this->answerFactory->create();

                    if (!empty($answerData['answer_id'])) {
                        $answer->load($answerData['answer_id']);
                        if (!$answer->getId()) {
                            throw new LocalizedException(__('Invalid answer ID.'));
                        }
                    }

                    $answer->setData('question_id', $questionId);
                    $answer->setData('title', $answerData['title']);
                    $answer->setData('description', $answerData['description']);
                    $answer->setData('sort_order', $answerData['sort_order']);
                    $answer->setData('is_correct', $answerData['is_correct'] ?? 0);
                    $answer->save();

                    $receivedAnswerIds[] = $answer->getId();
                }

                if (!empty($receivedAnswerIds)) {
                    try {
                        $answerCollection = $this->answerFactory->create()->getCollection()
                            ->addFieldToFilter('question_id', $questionId)
                            ->addFieldToFilter('answer_id', ['nin' => $receivedAnswerIds]);

                        foreach ($answerCollection as $answer) {
                            $answer->delete();
                        }
                    } catch (Exception $e) {
                        $this->messageManager->addErrorMessage(__(
                            'Error occurred while deleting old answers: ' . $e->getMessage()
                        ));
                    }
                }

            }
            $this->messageManager->addSuccessMessage(__('The question and answers have been saved.'));
            if (!$this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/');
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__(
                'An error occurred while saving the question. Please try again.'
            ));
        }

        return $resultRedirect->setPath('*/*/edit', ['id' => $questionId]);
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
