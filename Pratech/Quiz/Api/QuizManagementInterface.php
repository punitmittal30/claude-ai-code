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
namespace Pratech\Quiz\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface QuizManagementInterface
{
    /**
     * Get Quiz Data by Quiz ID
     *
     * @param  int $quizId
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQuizById($quizId): array;

    /**
     * Submit Quiz Result
     *
     * @param  int $userId
     * @param  int $quizId
     * @param  int $score
     * @return array
     */
    public function submitQuizResult(int $userId, int $quizId, int $score): array;
}
