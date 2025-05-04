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

namespace Pratech\Cart\Model;

use Pratech\Cart\Api\Data\AppliedRuleInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Object AppliedRule.
 */
class AppliedRule extends AbstractSimpleObject implements AppliedRuleInterface
{
    /**
     * @return int
     */
    public function getRuleId()
    {
        return $this->_get(self::RULE_ID);
    }

    /**
     * @param string $ruleId
     * @return $this
     */
    public function setRuleId($ruleId)
    {
        $this->setData(self::RULE_ID, $ruleId);
        return $this;
    }

    /**
     * @return string
     */
    public function getRuleName()
    {
        return $this->_get(self::RULE_NAME);
    }

    /**
     * @param string $ruleName
     * @return $this
     */
    public function setRuleName($ruleName)
    {
        $this->setData(self::RULE_NAME, $ruleName);
        return $this;
    }

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->_get(self::COUPON);
    }

    /**
     * @param string $coupon
     * @return $this
     */
    public function setCoupon($coupon)
    {
        $this->setData(self::COUPON, $coupon);
        return $this;
    }

    /**
     * @return string
     */
    public function getRuleDescription()
    {
        return $this->_get(self::RULE_DESCRIPTION);
    }

    /**
     * @param string $ruleDescription
     * @return $this
     */
    public function setRuleDescription($ruleDescription)
    {
        $this->setData(self::RULE_DESCRIPTION, $ruleDescription);
        return $this;
    }

    /**
     * @return string
     */
    public function getTermAndConditions()
    {
        return $this->_get(self::TERM_AND_CONDITIONS);
    }

    /**
     * @param string $termAndConditions
     * @return $this
     */
    public function setTermAndConditions($termAndConditions)
    {
        $this->setData(self::TERM_AND_CONDITIONS, $termAndConditions);
        return $this;
    }
}
