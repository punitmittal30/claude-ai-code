<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Position Option Provider Class
 */
class Position implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        for ($i = 0; $i <= 20; $i++) {
            $options[] = [
                'value' => $i,
                'label' => "$i"
            ];
        }
        return $options;
    }
}
