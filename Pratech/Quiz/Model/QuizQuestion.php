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

use Magento\Framework\Model\AbstractModel;

class QuizQuestion extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'quiz_question';

    /**
     * Quiz Question Model constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\QuizQuestion::class);
    }
}
