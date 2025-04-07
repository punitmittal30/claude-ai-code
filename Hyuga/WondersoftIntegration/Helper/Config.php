<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'wondersoft_integration/general/enabled';
    const XML_PATH_SERVER_IP = 'wondersoft_integration/general/server_ip';
    const XML_PATH_USERNAME = 'wondersoft_integration/general/username';
    const XML_PATH_PASSWORD = 'wondersoft_integration/general/password';
    const XML_PATH_LOG_ENABLED = 'wondersoft_integration/general/log_enabled';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context            $context,
        EncryptorInterface $encryptor
    )
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API Username
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUsername(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get API Password
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPassword(int $storeId = null): string
    {
        $password = $this->scopeConfig->getValue(
            self::XML_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $password ? $this->encryptor->decrypt($password) : '';
    }

    /**
     * Check if logging is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isLoggingEnabled(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Token API Endpoint
     *
     * @return string
     */
    public function getTokenEndpoint(): string
    {
        return $this->getServerIp() . '/eShopaidService.svc/token';
    }

    /**
     * Get Server IP
     *
     * @param int|null $storeId
     * @return string
     */
    public function getServerIp(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SERVER_IP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Process Data API Endpoint
     *
     * @return string
     */
    public function getProcessDataEndpoint(): string
    {
        return $this->getServerIp() . '/eShopaidService.svc/ProcessData';
    }
}
