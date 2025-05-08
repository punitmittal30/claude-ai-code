<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * XML Path constants
     */
    public const XML_PATH_ENABLED = 'wondersoft/general/enabled';
    public const XML_PATH_LOGGING_ENABLED = 'wondersoft/general/logging_enabled';
    public const XML_PATH_LOGGING_LEVEL = 'wondersoft/general/logging_level';
    public const XML_PATH_API_BASE_URL = 'wondersoft/api/base_url';
    public const XML_PATH_API_USERNAME = 'wondersoft/api/username';
    public const XML_PATH_API_PASSWORD = 'wondersoft/api/password';
    public const XML_PATH_TOKEN_LIFETIME = 'wondersoft/api/token_lifetime';
    public const XML_PATH_PRODUCT_PUSH_ENABLED = 'wondersoft/product_push/enabled';
    public const XML_PATH_PRODUCT_RETRY_FAILED = 'wondersoft/product_push/retry_failed';
    public const XML_PATH_COMPANY_CODE = 'wondersoft/product_push/company_code';
    public const XML_PATH_PRICE_PUSH_ENABLED = 'wondersoft/price_push/enabled';
    public const XML_PATH_PRICE_RETRY_FAILED = 'wondersoft/price_push/retry_failed';
    public const XML_PATH_PRICE_LIST_ID = 'wondersoft/price_push/price_list_id';
    public const XML_PATH_PRICE_LIST_NAME = 'wondersoft/price_push/price_list_name';

    public const XML_PATH_PRICE_REVISION_PUSH_ENABLED = 'wondersoft/price_revision/enabled';
    public const XML_PATH_PRICE_REVISION_PREFIX = 'wondersoft/price_revision/id_prefix';

    /**
     * Constructor
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context                    $context,
        private EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_LOGGING_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get logging level
     *
     * @return int
     */
    public function getLoggingLevel()
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LOGGING_LEVEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_BASE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API username
     *
     * @return string
     */
    public function getApiUsername(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_USERNAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get API password
     *
     * @return string
     */
    public function getApiPassword(): string
    {
        $password = $this->scopeConfig->getValue(
            self::XML_PATH_API_PASSWORD,
            ScopeInterface::SCOPE_STORE
        );

        return $this->encryptor->decrypt($password);
    }

    /**
     * Get token lifetime in minutes
     *
     * @return int
     */
    public function getTokenLifetime(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_TOKEN_LIFETIME,
            ScopeInterface::SCOPE_STORE
        ) ?: 30; // Default to 30 minutes
    }

    /**
     * Check if retry for failed product pushes is enabled
     *
     * @return bool
     */
    public function isProductRetryEnabled(): bool
    {
        return $this->isProductPushEnabled() && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_RETRY_FAILED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if product push is enabled
     *
     * @return bool
     */
    public function isProductPushEnabled(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_PUSH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get company code
     *
     * @return string
     */
    public function getCompanyCode(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_COMPANY_CODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if retry for failed price pushes is enabled
     *
     * @return bool
     */
    public function isPriceRetryEnabled(): bool
    {
        return $this->isPricePushEnabled() && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PRICE_RETRY_FAILED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if price push is enabled
     *
     * @return bool
     */
    public function isPricePushEnabled(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PRICE_PUSH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get price list ID
     *
     * @return string
     */
    public function getPriceListId(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_LIST_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get price list name
     *
     * @return string
     */
    public function getPriceListName(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_LIST_NAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if price revision push is enabled
     *
     * @return bool
     */
    public function isPriceRevisionPushEnabled(): bool
    {
        return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_PRICE_REVISION_PUSH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get price revision ID prefix
     *
     * @return string
     */
    public function getPriceRevisionPrefix(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_REVISION_PREFIX,
            ScopeInterface::SCOPE_STORE
        ) ?: 'PR';
    }

    /**
     * Generate a unique price revision ID
     *
     * @return string
     */
    public function generatePriceRevisionId(): string
    {
        $prefix = $this->getPriceRevisionPrefix();
        $date = date('Ymd');
        $random = substr(str_shuffle("0123456789"), 0, 6);

        return $prefix . $date . $random;
    }

    /**
     * Get Locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->scopeConfig->getValue(
            'general/locale/timezone',
            ScopeInterface::SCOPE_STORE
        );
    }
}
