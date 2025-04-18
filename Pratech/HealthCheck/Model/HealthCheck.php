<?php
/**
 * Pratech_HealthCheck
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\HealthCheck
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\HealthCheck\Model;

use Magento\AdvancedSearch\Model\Client\ClientResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Store\Model\ScopeInterface;
use Pratech\HealthCheck\Api\HealthCheckInterface;
use Pratech\RedisIntegration\Model\RedisConnection;
use Pratech\SqsIntegration\Model\SqsConnection;

/**
 * Health Check Class for AWS
 */
class HealthCheck implements HealthCheckInterface
{
    /**
     * Health Check Constructor
     *
     * @param SqsConnection $sqsConnection
     * @param RedisConnection $redisConnection
     * @param ClientResolver $clientResolver
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private SqsConnection        $sqsConnection,
        private RedisConnection      $redisConnection,
        private ClientResolver       $clientResolver,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        $sqsStatus = $this->sqsConnection->connect();
        if (!$sqsStatus) {
            throw new NotFoundException(__('SQS Connection Not Found'));
        }

        $redisStatus = $this->redisConnection->connect();
        if (!$redisStatus) {
            throw new NotFoundException(__('Redis Connection Failed'));
        }

        $elasticSearchStatus = $this->checkElasticSearchConnection();
        if (!$elasticSearchStatus) {
            throw new NotFoundException(__('ElasticSearch Connection Failed'));
        }

        return 'OK';
    }

    /**
     * Check ElasticSearch Connection
     *
     * @return boolean
     */
    private function checkElasticSearchConnection(): bool
    {
        $options = [
            'engine' => $this->getConfig('catalog/search/engine'),
            'hostname' => $this->getConfig('catalog/search/elasticsearch7_server_hostname'),
            'port' => $this->getConfig('catalog/search/elasticsearch7_server_port'),
            'index' => $this->getConfig('catalog/search/elasticsearch7_index_prefix'),
            'enableAuth' => $this->getConfig('catalog/search/elasticsearch7_enable_auth'),
            'username' => $this->getConfig('catalog/search/elasticsearch7_username'),
            'password' => $this->getConfig('catalog/search/elasticsearch7_password'),
            'timeout' => $this->getConfig('catalog/search/elasticsearch7_server_timeout'),
        ];

        return $this->clientResolver->create($options['engine'], $options)->testConnection();
    }

    /**
     * Get Config Value.
     *
     * @param string $path
     * @return mixed
     */
    private function getConfig(string $path): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
