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
namespace Pratech\Quiz\Model\ResourceModel\Quiz;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Quiz\Model\Quiz as QuizModel;
use Pratech\Quiz\Model\ResourceModel\Quiz as QuizResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'quiz_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(QuizModel::class, QuizResourceModel::class);
    }
}
