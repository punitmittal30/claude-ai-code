<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SqsIntegration\Model;

use Enqueue\Sqs\SqsConnectionFactory as Sqs;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\ConnectionLogger;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class to establish sqs connection.
 */
class SqsConnection
{
    /**
     * SQS KEY Constant
     */
    public const SQS_KEY = 'sqs/sqs/key';

    /**
     * SQS SECRET Constant
     */
    public const SQS_SECRET = 'sqs/sqs/secret';

    /**
     * SQS REGION Constant
     */
    public const SQS_REGION = 'sqs/sqs/region';

    /**
     * Sqs Connection Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectionLogger $connectionLogger
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ConnectionLogger     $connectionLogger,
        private EncryptorInterface   $encryptor
    ) {
    }

    /**
     * Connect SQS
     *
     * @return Sqs|null
     */
    public function connect(): ?Sqs
    {
        try {
            $sqsKey = $this->encryptor->decrypt(
                $this->getConfigValue(self::SQS_KEY)
            );

            $sqsSecret = $this->encryptor->decrypt(
                $this->getConfigValue(self::SQS_SECRET)
            );

            $sqsRegion = $this->getConfigValue(self::SQS_REGION);

            return new Sqs('sqs:?key=' . $sqsKey . '&secret=' . $sqsSecret . '&region=' . $sqsRegion);

        } catch (Exception $exception) {
            $this->connectionLogger->error($exception->getMessage() . __METHOD__);
        }
        return null;
    }

    /**
     * Get System Config Value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigValue(string $path): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
