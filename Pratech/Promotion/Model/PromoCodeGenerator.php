<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterfaceFactory;
use Pratech\Promotion\Model\Service\PromoCodeManagementService;

class PromoCodeGenerator
{
    /**
     * @var array
     */
    private $keyMap = ['quantity' => 'qty'];

    /**
     * @var PromoCodeManagementService
     */
    private $promotionManagementService;

    /**
     * @var PromoCodeGenerationSpecInterfaceFactory
     */
    private $generationSpecFactory;

    /**
     * @param PromoCodeManagementService $promotionManagementService
     * @param PromoCodeGenerationSpecInterfaceFactory $generationSpecFactory
     */
    public function __construct(
        PromoCodeManagementService              $promotionManagementService,
        PromoCodeGenerationSpecInterfaceFactory $generationSpecFactory
    ) {
        $this->promotionManagementService = $promotionManagementService;
        $this->generationSpecFactory = $generationSpecFactory;
    }

    /**
     * Generate Codes.
     *
     * @param array $parameters
     * @return string[]
     * @throws InputException
     * @throws LocalizedException
     */
    public function generateCodes(array $parameters)
    {
        $couponSpecData = $this->convertCouponSpecData($parameters);
        $couponSpec = $this->generationSpecFactory->create(['data' => $couponSpecData]);
        return $this->promotionManagementService->generate($couponSpec);
    }

    /**
     * We should map old values to new one
     *
     * @param array $data
     * @return array
     */
    private function convertCouponSpecData(array $data)
    {
        foreach ($this->keyMap as $mapKey => $mapValue) {
            $data[$mapKey] = isset($data[$mapValue]) ? $data[$mapValue] : null;
        }

        return $data;
    }
}
