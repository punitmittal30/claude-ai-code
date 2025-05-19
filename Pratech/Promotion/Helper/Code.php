<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Helper;

/**
 * Helper for promo codes creating and managing
 */
class Code extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const CODE_FORMAT_ALPHANUMERIC = 'alphanum';

    public const CODE_FORMAT_ALPHABETICAL = 'alpha';

    public const CODE_FORMAT_NUMERIC = 'num';

    public const XML_PATH_CAMPAIGN_CODE_LENGTH = 'campaign/auto_generated_codes/length';

    public const XML_PATH_CAMPAIGN_CODE_FORMAT = 'campaign/auto_generated_codes/format';

    /**
     * @var array
     */
    protected $_couponParameters;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param array $couponParameters
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        array $couponParameters
    ) {
        $this->_couponParameters = $couponParameters;
        parent::__construct($context);
    }
    /**
     * Get all possible promo codes formats
     *
     * @return array
     */
    public function getFormatsList()
    {
        return [
            self::CODE_FORMAT_ALPHANUMERIC => __('Alphanumeric'),
            self::CODE_FORMAT_ALPHABETICAL => __('Alphabetical'),
            self::CODE_FORMAT_NUMERIC => __('Numeric')
        ];
    }

    /**
     * Get default promo code length
     *
     * @return int
     */
    public function getDefaultLength()
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CAMPAIGN_CODE_LENGTH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get default promo code format
     *
     * @return int
     */
    public function getDefaultFormat()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CAMPAIGN_CODE_FORMAT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get promo's alphabet as array of chars
     *
     * @param string $format
     * @return array|bool
     */
    public function getCharset($format)
    {
        return str_split($this->_couponParameters['charset'][$format]);
    }

    /**
     * Retrieve Separator
     *
     * @return string
     */
    public function getCodeSeparator()
    {
        return $this->_couponParameters['separator'];
    }
}
