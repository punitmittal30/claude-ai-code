<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const VINCULUM_BASE_URL = 'warehouse/vinculum/base_url';

    private const VINCULUM_API_OWNER = 'warehouse/vinculum/api_owner';

    private const VINCULUM_API_KEY = 'warehouse/vinculum/api_key';

    private const DROPSHIP_DELIVERY_ETA = 'warehouse/general/dropship_delivery_eta';

    private const STATIC_ATTRIBUTES = 'warehouse/general/static_attributes';

    private const DYNAMIC_ATTRIBUTES = 'warehouse/general/dynamic_attributes';

    /**
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private EncryptorInterface   $encryptor,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get Config.
     *
     * @param string $config
     * @return mixed
     */
    public function getConfig(string $config): mixed
    {
        return $this->scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Vinculum Base URL
     *
     * @return string
     */
    public function getVinculumBaseUrl(): string
    {
        return $this->getConfig(self::VINCULUM_BASE_URL);
    }

    /**
     * Get Vinculum API Owner
     *
     * @return string
     */
    public function getVinculumApiOwner(): string
    {
        $owner = $this->getConfig(self::VINCULUM_API_OWNER);
        return $this->encryptor->decrypt($owner);
    }

    /**
     * Get Vinculum API Key
     *
     * @return string
     */
    public function getVinculumApiKey(): string
    {
        $key = $this->getConfig(self::VINCULUM_API_KEY);
        return $this->encryptor->decrypt($key);
    }

    /**
     * Get Dropship Delivery ETA.
     *
     * @return string
     */
    public function getDropshipDeliveryEta(): string
    {
        return $this->getConfig(self::DROPSHIP_DELIVERY_ETA);
    }

    /**
     * Get Static Attributes.
     *
     * @return string[]
     */
    public function getStaticAttributes(): array
    {
        $allowedAttributes = $this->scopeConfig->getValue(
            self::STATIC_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );
        return explode(',', $allowedAttributes);
    }

    /**
     * Get Dynamic Attributes.
     *
     * @return string[]
     */
    public function getDynamicAttributes(): array
    {
        $allowedAttributes = $this->scopeConfig->getValue(
            self::DYNAMIC_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );
        return explode(',', $allowedAttributes);
    }
}
