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

use Magento\Framework\Option\ArrayInterface;
use Magento\Cron\Model\Schedule;

class Status implements ArrayInterface
{
    /**
     * Status Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $statuses = [
            [
                'value' => 'pass',
                'label' => '<span class="grid-severity-notice">'
                    . htmlspecialchars(__("Pass"))
                    . '</span>'
            ],
            [
                'value' => 'fail',
                'label' => '<span class="grid-severity-minor">'
                    . htmlspecialchars(__("Fail"))
                    . '</span>'
            ],
        ];

        return $statuses;
    }
}
