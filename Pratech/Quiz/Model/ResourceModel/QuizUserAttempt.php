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
namespace Pratech\Quiz\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuizUserAttempt extends AbstractDb
{
     /**
      * @var string
      */
    protected $_idFieldName = 'attempt_id';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('quiz_user_attempt', 'attempt_id');
    }
}
