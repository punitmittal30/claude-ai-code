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

namespace Pratech\Quiz\Block\Adminhtml\QuizQuestion\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Generic Button for providing url and quiz question id.
 */
class Generic
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Generic constructor
     *
     * @param Context  $context
     * @param Registry $registry
     */
    public function __construct(
        Context  $context,
        protected Registry $registry
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        $quizData = $this->registry->registry('quiz_question');
        return $quizData ? $quizData->getId() : null;
    }

    /**
     * Get Url
     *
     * @param  string $route
     * @param  array  $param
     * @return string
     */
    public function getUrl($route = '', $param = []): string
    {
        return $this->urlBuilder->getUrl($route, $param);
    }
}
