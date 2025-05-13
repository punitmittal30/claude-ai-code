<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Api\SystemConfigInterface;
use Pratech\Base\Model\Data\Response;

/**
 * System Config model class to return system config values.
 */
class SystemConfig implements SystemConfigInterface
{
    /**
     * Constant for success code.
     */
    public const SUCCESS_CODE = 200;

    /**
     * Constant for system config api resource.
     */
    public const SYSTEM_CONFIG_API_RESOURCE = 'system_config';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Response $response
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Response             $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOtpByPassNumbers(): array
    {
        $mobileNumbers = [];

        $numbers = $this->scopeConfig->getValue(
            'customers/general/otp_bypass_numbers',
            ScopeInterface::SCOPE_STORE
        );

        if ($numbers) {
            $mobileNumbers = explode(',', $numbers);
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::SYSTEM_CONFIG_API_RESOURCE,
            [
                "mobile_numbers" => $mobileNumbers
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getFooterQuickLinks(): array
    {
        $links = [];

        $quickLinks = $this->scopeConfig->getValue(
            'footer/general/quick_links',
            ScopeInterface::SCOPE_STORE
        );

        if ($quickLinks) {
            $quickLinks = json_decode($quickLinks, true);

            foreach ($quickLinks as $link) {
                $links[] = [
                    'label' => $link['label'] ?? '', 
                    'url'   => $link['url'] ?? ''
                ];
            }
        }
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::SYSTEM_CONFIG_API_RESOURCE,
            $links
        );
    }
}
