<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model\Config\Source;

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
        for ($i = 0; $i < 20; $i++) {
            $options[] = [
                'value' => $i,
                'label' => "$i"
            ];
        }
        return $options;
    }
}
