<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Priority Option Provider Class
 */
class Priority implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        for ($i = 0; $i < 20; $i++) {
            $options[] = [
                'value' => $i,
                'label' => "$i"
            ];
        }
        return $options;
    }
}
