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
namespace Pratech\Quiz\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Quiz\Model\ResourceModel\Quiz\CollectionFactory;

/**
 * Class QuizList
 * Provides a list of quizzes for dropdown options.
 */
class QuizList implements OptionSourceInterface
{
    /**
     * Constructor
     *
     * @param CollectionFactory $quizCollectionFactory
     */
    public function __construct(
        protected CollectionFactory $quizCollectionFactory
    ) {
    }

    /**
     * Get quiz list as options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->quizCollectionFactory->create();

        foreach ($collection as $quiz) {
            $options[] = [
                'value' => $quiz->getId(),
                'label' => $quiz->getTitle(),
            ];
        }

        return $options;
    }
}
