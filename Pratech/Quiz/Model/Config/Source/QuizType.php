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

/**
 * Class QuizType
 * Provides options for Quiz types
 */
class QuizType implements OptionSourceInterface
{
    public const POPUP = 'popup';
    public const GAME = 'game';

    /**
     * Get options for reward types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::POPUP, 'label' => __('Popup')],
            ['value' => self::GAME, 'label' => __('Game')],
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::POPUP => __('Popup'),
            self::GAME => __('Game'),
        ];
    }
}
