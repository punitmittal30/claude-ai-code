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
 * Class PassingMark
 * Provides options for passing mark in percentage
 */
class PassingMark implements OptionSourceInterface
{
    /**
     * Get options for passing mark
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        for ($i = 30; $i <= 100; $i += 5) {
            $options[] = [
                'value' => $i,
                'label' => sprintf('%d%%', $i)
            ];
        }
        return $options;
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = [];
        for ($i = 30; $i <= 100; $i += 5) {
            $options[$i] = sprintf('%d%%', $i);
        }
        return $options;
    }
}
