<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Plugin\Model;

use Amasty\Coupons\Model\DiscountCollector;

/**
 * Discount Collector Preference class to override its methods.
 */
class DiscountCollectorPlugin extends DiscountCollector
{
    /**
     * Return amount of discount for each rule
     *
     * @return array
     */
    public function getRulesWithAmount(): array
    {
        if (empty($this->amount)) {
            $this->restoreDataForBreakdown();
        }

        $totalAmount = [];

        foreach ($this->amount as $ruleCode => $ruleAmount) {
            $totalAmount[] = [
                'coupon_code' => (string)$ruleCode,
                'coupon_amount' =>
                    '-' . number_format($ruleAmount, 2, '.', '')
            ];
        }

        return $totalAmount;
    }
}
