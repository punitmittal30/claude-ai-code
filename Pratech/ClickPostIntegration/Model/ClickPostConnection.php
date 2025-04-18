<?php
/**
 * Pratech_ClickPostIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ClickPostIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ClickPostIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;

/**
 * Click Post Connection Class to call click post api
 */
class ClickPostConnection
{
    /**
     * Click Post Estimated Delivery Date Url
     */
    public const CLICK_POST_EDD_API_URL = 'redis/click_post/edd_api_url';

    /**
     * Click Post Integration Key
     */
    public const CLICK_POST_KEY = 'redis/click_post/key';

    /**
     * Click Post Integration Username
     */
    public const CLICK_POST_USERNAME = 'redis/click_post/username';

    /**
     * Click Post Origin Pincode
     */
    public const CLICK_POST_ORIGIN = 'redis/click_post/origin';

    /**
     * EDD Constructor
     *
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     */
    public function __construct(
        private EncryptorInterface   $encryptor,
        private ScopeConfigInterface $scopeConfig,
        private Curl                 $curl
    ) {
    }

    /**
     * Get Estimated Delivery Date
     *
     * @param string $destination
     * @param string $origin
     * @return array
     */
    public function getEstimatedDeliveryDate(string $destination, string $origin = ""): array
    {
        if (empty($origin)) {
            $origin = $this->scopeConfig->getValue(
                self::CLICK_POST_ORIGIN,
                ScopeInterface::SCOPE_STORE
            );
        }

        $body[] = [
            "pickup_pincode" => $origin,
            "drop_pincode" => $destination
        ];

        $url = $this->getEddApiUrl();

        if (null != $url) {
            $urlParams = [
                "username" => $this->getUsername(),
                "key" => $this->getKey()
            ];

            $apiUrl = $url . '?' . http_build_query($urlParams);

            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($apiUrl, json_encode($body));
            return json_decode($this->curl->getBody(), true);
        }
        return [];
    }

    /**
     * Get EDD Api Url
     *
     * @return string|null
     */
    private function getEddApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::CLICK_POST_EDD_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Click Post Username
     *
     * @return string
     */
    private function getUsername(): string
    {
        $username = $this->scopeConfig->getValue(
            self::CLICK_POST_USERNAME,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($username);
    }

    /**
     * Get Click Post Key
     *
     * @return string
     */
    private function getKey(): string
    {
        $key = $this->scopeConfig->getValue(
            self::CLICK_POST_KEY,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($key);
    }
}
