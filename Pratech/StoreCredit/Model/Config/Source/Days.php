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
 * Days array Source Class
 */
class Days implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        for ($i = 0; $i <= 30; $i++) {
            $options[] = ['value' => $i, 'label' => $i . ' Days'];
        }
        return $options;
    }
}
