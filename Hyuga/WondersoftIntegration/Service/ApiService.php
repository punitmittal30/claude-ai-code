<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Service;

use Exception;
use Hyuga\WondersoftIntegration\Helper\Config;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use SimpleXMLElement;

class ApiService
{
    private const METHOD_GET_TOKEN = 'GetToken';
    private const METHOD_PUSH_ITEM = 'PushItem';
    private const METHOD_PUSH_PRICE_LIST = 'PushPriceList';

    /**
     * @var string|null
     */
    private $token = null;

    /**
     * ApiService constructor.
     *
     * @param Config $config
     * @param Logger $logger
     * @param Client $httpClient
     * @param Json $jsonSerializer
     */
    public function __construct(
        private Config $config,
        private Logger $logger,
        private Client $httpClient,
        private Json   $jsonSerializer
    )
    {
    }

    /**
     * Push item to eShopaid
     *
     * @param array $itemData
     * @return array
     * @throws LocalizedException
     */
    public function pushItem(array $itemData): array
    {
        $xml = $this->prepareItemXml($itemData);
        return $this->sendProcessDataRequest(self::METHOD_PUSH_ITEM, $xml);
    }

    /**
     * Prepare item XML
     *
     * @param array $itemData
     * @return string
     */
    private function prepareItemXml(array $itemData): string
    {
        $xml = new SimpleXMLElement('<MasterItem></MasterItem>');
        $item = $xml->addChild('Item');

        foreach ($itemData as $key => $value) {
            // Handle special cases like attributes and barcodes
            if ($key === 'Attributes' && is_array($value)) {
                $attributes = $item->addChild('Attributes');
                foreach ($value as $attribute) {
                    $attributeNode = $attributes->addChild('Attribute');
                    foreach ($attribute as $attrKey => $attrValue) {
                        $attributeNode->addChild($attrKey, $attrValue);
                    }
                }
            } elseif ($key === 'Barcodes' && is_array($value)) {
                $barcodes = $item->addChild('Barcodes');
                foreach ($value as $barcode) {
                    $barcodeNode = $barcodes->addChild('Barcode');
                    foreach ($barcode as $barKey => $barValue) {
                        $barcodeNode->addChild($barKey, $barValue);
                    }
                }
            } else {
                // Regular fields
                $item->addChild($key, (string)$value);
            }
        }

        return $xml->asXML();
    }

    /**
     * Send request to ProcessData endpoint
     *
     * @param string $methodName
     * @param string $requestXml
     * @return array
     * @throws LocalizedException
     */
    private function sendProcessDataRequest(string $methodName, string $requestXml): array
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('Wondersoft eShopaid integration is disabled'));
        }

        $endpoint = $this->config->getProcessDataEndpoint();
        $token = $this->getToken();

        $this->logger->info('Sending ' . $methodName . ' request to: ' . $endpoint);
        $this->logger->info('Request data: ' . $requestXml);

        $this->httpClient->reset();
        $this->httpClient->setUri($endpoint);
        $this->httpClient->setMethod(Request::METHOD_POST);
        $this->httpClient->setHeaders([
            'SERVICE_METHODNAME' => $methodName,
            'AUTHORIZATION' => $token,
            'Content-Type' => 'application/xml'
        ]);
        $this->httpClient->setRawBody($requestXml);

        try {
            $response = $this->httpClient->send();

            if ($response->getStatusCode() !== 200) {
                throw new LocalizedException(
                    __('API request failed. Status code: %1, Message: %2',
                        $response->getStatusCode(),
                        $response->getReasonPhrase()
                    )
                );
            }

            $responseBody = $response->getBody();
            $this->logger->info('Response: ' . $responseBody);

            // Parse XML response
            $xml = simplexml_load_string($responseBody);

            if ((string)$xml->Result === 'FAILURE') {
                $failureReason = (string)$xml->FailureReason ?: 'Unknown error';
                throw new LocalizedException(__('API request failed: %1', $failureReason));
            }

            // Convert XML to array and return
            return $this->xmlToArray($xml);

        } catch (Exception $e) {
            $this->logger->error('Error in API request: ' . $e->getMessage());
            throw new LocalizedException(__('Error in API request: %1', $e->getMessage()));
        }
    }

    /**
     * Generate and return token
     *
     * @return string
     * @throws LocalizedException
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('Wondersoft eShopaid integration is disabled'));
        }

        $endpoint = $this->config->getTokenEndpoint();
        $username = $this->config->getUsername();
        $password = $this->config->getPassword();

        $this->logger->info('Requesting token from: ' . $endpoint);

        $this->httpClient->reset();
        $this->httpClient->setUri($endpoint);
        $this->httpClient->setMethod(Request::METHOD_POST);
        $this->httpClient->setHeaders([
            'SERVICE_METHODNAME' => self::METHOD_GET_TOKEN,
            'Username' => $username,
            'Password' => $password
        ]);

        try {
            $response = $this->httpClient->send();

            if ($response->getStatusCode() === 401) {
                throw new LocalizedException(__('Invalid credentials for Wondersoft eShopaid API'));
            }

            if ($response->getStatusCode() !== 200) {
                throw new LocalizedException(
                    __('Failed to get token. Status code: %1, Message: %2',
                        $response->getStatusCode(),
                        $response->getReasonPhrase()
                    )
                );
            }

            $responseBody = $response->getBody();
            $this->logger->info('Token response: ' . $responseBody);

            // Parse XML response to extract token
            $xml = simplexml_load_string($responseBody);

            if ((string)$xml->Result !== 'SUCCESS') {
                throw new LocalizedException(__('Failed to get token. API returned error.'));
            }

            $this->token = (string)$xml->Access_Token;
            return $this->token;

        } catch (Exception $e) {
            $this->logger->error('Error getting token: ' . $e->getMessage());
            throw new LocalizedException(__('Error getting token: %1', $e->getMessage()));
        }
    }

    /**
     * Convert SimpleXMLElement to array
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    private function xmlToArray(SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        return $this->jsonSerializer->unserialize($json);
    }

    /**
     * Push price list to eShopaid
     *
     * @param array $priceListData
     * @return array
     * @throws LocalizedException
     */
    public function pushPriceList(array $priceListData): array
    {
        $xml = $this->preparePriceListXml($priceListData);
        return $this->sendProcessDataRequest(self::METHOD_PUSH_PRICE_LIST, $xml);
    }

    /**
     * Prepare price list XML
     *
     * @param array $priceListData
     * @return string
     */
    private function preparePriceListXml(array $priceListData): string
    {
        $xml = new SimpleXMLElement('<PriceList></PriceList>');

        foreach ($priceListData as $product) {
            $productNode = $xml->addChild('Product');
            foreach ($product as $key => $value) {
                $productNode->addChild($key, (string)$value);
            }
        }

        return $xml->asXML();
    }
}
