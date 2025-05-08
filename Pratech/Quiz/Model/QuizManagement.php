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

namespace Pratech\Quiz\Model;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Model\Data\Response;
use Pratech\Quiz\Api\QuizManagementInterface;
use Pratech\Quiz\Model\ResourceModel\Quiz\CollectionFactory as QuizCollectionFactory;
use Pratech\Quiz\Model\ResourceModel\QuizAnswer\CollectionFactory as AnswerCollectionFactory;
use Pratech\Quiz\Model\ResourceModel\QuizQuestion\CollectionFactory as QuestionCollectionFactory;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

class QuizManagement implements QuizManagementInterface
{
    /**
     * Constant for FILTER API RESOURCE
     */
    public const QUIZ_API_RESOURCE = 'quiz';

    /**
     * @param Response $response
     * @param QuizCollectionFactory $quizCollectionFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param AnswerCollectionFactory $answerCollectionFactory
     * @param QuizUserAttemptFactory $quizUserAttemptFactory
     * @param StoreCreditHelper $storeCreditHelper
     */
    public function __construct(
        private Response                  $response,
        private QuizCollectionFactory     $quizCollectionFactory,
        private QuestionCollectionFactory $questionCollectionFactory,
        private AnswerCollectionFactory   $answerCollectionFactory,
        private QuizUserAttemptFactory    $quizUserAttemptFactory,
        private StoreCreditHelper         $storeCreditHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getQuizById($quizId): array
    {
        $quizCollection = $this->quizCollectionFactory->create()
            ->addFieldToFilter('quiz_id', $quizId)
            ->addFieldToFilter('status', 1)
            ->getFirstItem();

        if (!$quizCollection->getId()) {
            throw new NoSuchEntityException(__('Quiz with ID %1 does not exist.', $quizId));
        }

        $questions = $this->questionCollectionFactory->create()
            ->addFieldToFilter('quiz_id', $quizId);

        $result = [
            "title" => $quizCollection->getTitle(),
            "description" => $quizCollection->getDescription(),
            "terms_conditions" => $quizCollection->getTermsConditions(),
            "passing_mark" => $quizCollection->getPassingMark(),
            "quiz_type" => $quizCollection->getQuizType(),
            "quiz_for" => $quizCollection->getQuizFor(),
            "allow_retry" => $quizCollection->getAllowRetry(),
            "reward_type" => $quizCollection->getRewardType(),
            "reward_value" => $quizCollection->getRewardValue(),
            "reward_description" => $quizCollection->getRewardDescription()
        ];

        foreach ($questions as $question) {
            $questionData = [
                "question_text" => $question->getQuestionText(),
                "question_type" => $question->getQuestionType(),
            ];

            $answers = $this->answerCollectionFactory->create()
                ->addFieldToFilter('question_id', $question->getId())
                ->setOrder('sort_order', 'ASC');

            foreach ($answers as $answer) {
                $questionData['answers'][] = [
                    "title" => $answer->getTitle(),
                    "description" => $answer->getDescription(),
                    "is_correct" => $answer->getIsCorrect(),
                ];
            }

            $result['questions'][] = $questionData;
        }

        return $this->response->getResponse(
            200,
            'success',
            self::QUIZ_API_RESOURCE,
            $result
        );
    }

    /**
     * @inheritDoc
     */
    public function submitQuizResult($userId, $quizId, $score): array
    {
        try {
            $quiz = $this->quizCollectionFactory->create()
                ->addFieldToFilter('quiz_id', $quizId)
                ->addFieldToFilter('status', 1)
                ->getFirstItem();

            if (!$quiz->getId()) {
                throw new NoSuchEntityException(__('Quiz with ID %1 does not exist.', $quizId));
            }

            $passingScore = $quiz->getPassingMark();
            $allowRetry = $quiz->getAllowRetry();

            $existingAttempt = $this->quizUserAttemptFactory->create()
                ->getCollection()
                ->addFieldToFilter('user_id', $userId)
                ->addFieldToFilter('quiz_id', $quizId)
                ->setOrder('attempted_at', 'DESC')
                ->getFirstItem();

            if ($existingAttempt->getId()) {
                $existingScore = $existingAttempt->getScore();

                if ($existingScore >= $passingScore || !$allowRetry) {
                    return [
                        'message' => __('You have already submitted the quiz.'),
                        'status' => false
                    ];
                }
            }

            $status = ($score >= $passingScore) ? 'pass' : 'fail';

            if ($quiz->getRewardType() == 'hcash') {
                $this->storeCreditHelper->addStoreCredit(
                    (int)$userId,
                    (float)$quiz->getRewardValue(),
                    "Cashback credited against quiz",
                    [
                        'event_name' => 'quiz',
                        'quiz_id' => (int)$quizId
                    ]
                );
            }
            $data = [
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'score' => $score,
                'status' => $status,
                'reward' => $quiz->getRewardType() . ": " . $quiz->getRewardValue(),
            ];

            $userAttempt = $this->quizUserAttemptFactory->create();
            $userAttempt->setData($data);
            $userAttempt->save();

            return [
                'message' => __('Quiz result submitted successfully.'),
                'status' => true
            ];
        } catch (NoSuchEntityException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => __('An error occurred while submitting the quiz. Please try again later.')
            ];
        }
    }
}
