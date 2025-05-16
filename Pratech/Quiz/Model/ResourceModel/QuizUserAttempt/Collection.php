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
namespace Pratech\Quiz\Model\ResourceModel\QuizUserAttempt;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Quiz\Model\QuizUserAttempt as QuizUserAttemptModel;
use Pratech\Quiz\Model\ResourceModel\QuizUserAttempt as QuizUserAttemptResourceModel;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'attempt_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(QuizUserAttemptModel::class, QuizUserAttemptResourceModel::class);
    }
}
