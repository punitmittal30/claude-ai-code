<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * StoreCredit Type Source Class
 */
class StoreCreditApply implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => 'Percent of Product Price',
                'value' => 'percent'
            ],
            [
                'label' => 'Fixed Store Credit Point',
                'value' => 'fixed'
            ]
        ];
    }
}
