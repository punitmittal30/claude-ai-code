<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface AppliedRuleInterface extends ExtensibleDataInterface
{
    /**
     * Constant used as key into $_data
     */
    public const RULE_ID = 'rule_id';
    public const RULE_NAME = 'rule_name';
    public const COUPON = 'coupon';
    public const RULE_DESCRIPTION = 'rule_description';
    public const TERM_AND_CONDITIONS = 'term_and_conditions';

    /**
     * @return int|null
     */
    public function getRuleId();

    /**
     * @param int $ruleId
     * @return $this
     */
    public function setRuleId($ruleId);

    /**
     * @return string|null
     */
    public function getRuleName();

    /**
     * @param string $ruleName
     * @return $this
     */
    public function setRuleName($ruleName);

    /**
     * @return string
     */
    public function getCoupon();

    /**
     * @param string $coupon
     * @return $this
     */
    public function setCoupon($coupon);

    /**
     * @return string
     */
    public function getRuleDescription();

    /**
     * @param string $ruleDescription
     * @return $this
     */
    public function setRuleDescription($ruleDescription);

    /**
     * @return string
     */
    public function getTermAndConditions();

    /**
     * @param string $termAndConditions
     * @return $this
     */
    public function setTermAndConditions($termAndConditions);
}
