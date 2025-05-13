<?php

namespace Pratech\Quiz\Model\Ui\QuizQuestion;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Quiz\Model\ResourceModel\QuizQuestion\CollectionFactory;
use Pratech\Quiz\Model\ResourceModel\QuizAnswer\CollectionFactory as AnswerCollectionFactory;

/**
 * Class DataProvider
 * Provides data for Quiz UI Component Form
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var array
     */
    private $loadedData = [];

    /**
     * Constructor
     *
     * @param string                  $name
     * @param string                  $primaryFieldName
     * @param string                  $requestFieldName
     * @param CollectionFactory       $collectionFactory
     * @param AnswerCollectionFactory $answerCollectionFactory
     * @param DataPersistorInterface  $dataPersistor
     * @param array                   $meta
     * @param array                   $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private AnswerCollectionFactory $answerCollectionFactory,
        private DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data for UI Component
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $quiz) {
            $data = $quiz->getData();

            // Load answers for the current question
            $answers = $this->getAnswersData($quiz->getId());
            $data['answers_data'] = $answers;

            $this->loadedData[$quiz->getId()] = $data;
        }

        $data = $this->dataPersistor->get('quiz_question');
        if (!empty($data)) {
            $quiz = $this->collection->getNewEmptyItem();
            $quiz->setData($data);

            // Load answers for new data if available
            $quizData = $quiz->getData();
            $quizData['answers_data'] = $data['answers_data'] ?? [];
            $this->loadedData[$quiz->getId()] = $quizData;

            $this->dataPersistor->clear('quiz_question');
        }

        return $this->loadedData;
    }

    /**
     * Get answers data for a specific question
     *
     * @param  int $questionId
     * @return array
     */
    private function getAnswersData($questionId): array
    {
        $answerCollection = $this->answerCollectionFactory->create()
            ->addFieldToFilter('question_id', $questionId);

        $answers = [];
        foreach ($answerCollection as $answer) {
            $answers[] = [
                'answer_id' => $answer->getId(),
                'title' => $answer->getTitle(),
                'description' => $answer->getDescription(),
                'sort_order' => $answer->getSortOrder(),
                'is_correct' => $answer->getIsCorrect()
            ];
        }

        return $answers;
    }
}
