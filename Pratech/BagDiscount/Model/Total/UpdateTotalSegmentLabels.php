<?php
/**
 * Pratech_BagDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\BagDiscount
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\BagDiscount\Model\Total;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\SalesRule\Model\Quote\Discount as DiscountCollector;

/**
 * Update Total Segment Labels
 */
class UpdateTotalSegmentLabels extends AbstractTotal
{

    /**
     * Fetch Totals Segment
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        $result = [];
        if ($total->getDiscountAmount()) {
            $result[] = [
                'code' => DiscountCollector::COLLECTOR_TYPE_CODE,
                'title' => __('Offers Applied'),
                'value' => $total->getDiscountAmount()
            ];
        }
        return $result;
    }
}
