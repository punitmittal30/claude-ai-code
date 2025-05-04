<?php
/**
 * Pratech_SlackNotifier
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SlackNotifier
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SlackNotifier\Helper;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;

/**
 * Slack Notifier helper class
 */
class SlackNotifier
{
    const SLACK_NOTIFIER_ENABLED = 'slack_notifier/general/enabled';
    const SLACK_NOTIFIER_WEBHOOK = 'slack_notifier/general/webhook_url';
    const SLACK_BOT_TOKEN = 'slack_notifier/general/boat_oauth_token';
    const SLACK_CHANNELS = 'slack_notifier/general/channels';


    const GET_UPLOAD_URL = "https://slack.com/api/files.getUploadURLExternal";
    const FILE_UPLOAD_URL = "https://slack.com/api/files.completeUploadExternal";


    /**
     * SlackNotifier Helper Constructor
     *
     * @param Logger $logger
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private Logger               $logger,
        private Curl                 $curl,
        private ScopeConfigInterface $scopeConfig,
        private EncryptorInterface   $encryptor,
    )
    {
    }

    /**
     * Get System Config
     *
     * @param string $configPath
     * @return mixed
     */
    private function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Slack Webhook Url
     *
     * @return string
     */
    public function getWebhookUrl(): string
    {
        return $this->getConfig(self::SLACK_NOTIFIER_WEBHOOK);
    }

    /**
     * Get Slack Bot OAuth Token
     *
     * @return string
     */
    public function getToken(): string
    {
        $owner = $this->getConfig(
            self::SLACK_BOT_TOKEN
        );
        return $this->encryptor->decrypt($owner);
    }

    /**
     * Send Message to Slack
     *
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $message): bool
    {
        $isEnabled = $this->getConfig(self::SLACK_NOTIFIER_ENABLED);
        $webhookUrl = $this->getWebhookUrl();

        if (!$isEnabled || empty($webhookUrl)) {
            return false;
        }

        try {
            $payload = json_encode(['text' => $message]);

            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->post($webhookUrl, $payload);
            return true;
        } catch (Exception $e) {
            $this->logger->info('Slack notification failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send Csv Content to Slack
     *
     * @param string $csvContent
     * @param string $filename
     * @return bool
     * @throws Exception
     */
    public function sendCsvContent(string $csvContent, string $filename): bool
    {
        try {
            $isEnabled = $this->getConfig(self::SLACK_NOTIFIER_ENABLED);
            $slackToken = $this->getToken();
            $channels = $this->getConfig(self::SLACK_CHANNELS);

            if (!$isEnabled || empty($filename) || empty($slackToken) || empty($channels)) {
                return false;
            }

            if (!preg_match('/^[\w\-\.]+\.csv$/', $filename)) {
                throw new InvalidArgumentException('Invalid filename format');
            }

            $fileSize = mb_strlen($csvContent, '8bit');
            if ($fileSize === 0) {
                throw new InvalidArgumentException('CSV content cannot be empty');
            }

            $client = new Client();

            $uploadResponse = $client->get(
                self::GET_UPLOAD_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $slackToken,
                        'Accept-Encoding' => 'gzip',
                        'Content-Type' => 'application/json; charset=utf-8',
                    ],
                    'query' => [
                        'filename' => $filename,
                        'length' => (int)$fileSize,
                        'pretty' => 1,
                    ],
                ]
            );

            $uploadData = json_decode($uploadResponse->getBody(), true);

            if (!$uploadData['ok']) {
                $this->logger->info(
                    'Upload URL Error: ' . json_encode(
                        [
                            'error' => $uploadData['error'] ?? 'Unknown error',
                            'response' => $uploadData
                        ]
                    )
                );
                return false;
            }

            $putResponse = $client->request(
                'POST', $uploadData['upload_url'], [
                    'headers' => [
                        'Content-Type' => 'text/csv',
                    ],
                    'body' => $csvContent
                ]
            );

            if ($putResponse->getStatusCode() !== 200) {
                $this->logger->info('File upload failed with status: ' . $putResponse->getStatusCode());
                return false;
            }

            $completeResponse = $client->post(
                self::FILE_UPLOAD_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $slackToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'files' => [
                            [
                                'id' => $uploadData['file_id'],
                                'title' => $filename
                            ]
                        ],
                        'channels' => $channels,
                        'initial_comment' => 'Return Report'
                    ]
                ]
            );

            $completeData = json_decode($completeResponse->getBody(), true);
            if (!$completeData['ok']) {
                $this->logger->info('Complete Upload Error: ' . ($completeData['error'] ?? 'Unknown error'));
                return false;
            }

        } catch (Exception $e) {
            $this->logger->info('Slack upload failed: ' . $e->getMessage());
            return false;
        }
        return true;
    }
}
