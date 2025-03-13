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
    public const CLICK_POST_EDD_API_URL = 'warehouse/clickpost/base_url';

    public const CLICK_POST_KEY = 'warehouse/clickpost/api_key';

    public const CLICK_POST_USERNAME = 'warehouse/clickpost/api_username';

    public const CLICK_POST_ORIGIN = 'warehouse/clickpost/origin';

    // https://hyugalife.vineretail.com/RestWS/api/eretail/v1/sku/getlotlevelinventory
    private const VINCULUM_BASE_URL = 'warehouse/vinculum/base_url';

    // Shivam
    private const VINCULUM_API_OWNER = 'warehouse/vinculum/api_owner';

    // abed1993238f4dddab23fa229becfc941a205243f31a4ba7a8b178a
    private const VINCULUM_API_KEY = 'warehouse/vinculum/api_key';

    // yryn5ynasicjcqqqpeoslczkn40hxtbx
    private const VINCULUM_BEARER_TOKEN = 'warehouse/vinculum/bearer_token';

    private const DROPSHIP_DELIVERY_ETA = 'warehouse/general/dropship_delivery_eta';

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
     * Get EDD Api Url
     *
     * @return string|null
     */
    public function getClickpostEddApiUrl(): ?string
    {
        return $this->getConfig(self::CLICK_POST_EDD_API_URL);
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
     * Get Click Post Username
     *
     * @return string
     */
    public function getClickpostUsername(): string
    {
        $username = $this->getConfig(self::CLICK_POST_USERNAME);
        return $this->encryptor->decrypt($username);
    }

    /**
     * Get Click Post Key
     *
     * @return string
     */
    public function getClickpostKey(): string
    {
        $key = $this->getConfig(self::CLICK_POST_KEY);
        return $this->encryptor->decrypt($key);
    }

    /**
     * Get Click Post Default Origin Pincode
     *
     * @return string
     */
    public function getClickpostOriginPincode(): string
    {
        return $this->getConfig(self::CLICK_POST_ORIGIN);
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
     * Get Vinculum Bearer Token
     *
     * @return string
     */
    public function getVinculumBearerToken(): string
    {
        $key = $this->getConfig(self::VINCULUM_BEARER_TOKEN);
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
}
