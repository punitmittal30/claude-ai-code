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
namespace Pratech\Quiz\Model\ResourceModel\QuizAnswer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Quiz\Model\QuizAnswer as QuizAnswerModel;
use Pratech\Quiz\Model\ResourceModel\QuizAnswer as QuizAnswerResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'answer_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(QuizAnswerModel::class, QuizAnswerResourceModel::class);
    }
}
