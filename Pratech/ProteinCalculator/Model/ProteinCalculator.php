<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ProteinCalculator\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\ProteinCalculator\Api\ProteinCalculatorInterface;
use Pratech\ProteinCalculator\Helper\Data as ProteinCalculatorHelper;

class ProteinCalculator implements ProteinCalculatorInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * CART API RESOURCE
     */
    private const PROTEIN_CALCULATOR_API_RESOURCE = 'protein_calculator';

    /**
     * Store Credit Constructor
     *
     * @param ProteinCalculatorHelper $proteinCalculatorHelper
     * @param Response $response
     */
    public function __construct(
        private ProteinCalculatorHelper $proteinCalculatorHelper,
        private Response $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProteinData(
        int $age,
        int $weight,
        string $height,
        string $gender,
        string $bodyType,
        string $dietType,
        string $goal,
        string $budget
    ): array {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::PROTEIN_CALCULATOR_API_RESOURCE,
            $this->proteinCalculatorHelper->calculateProtein(
                $age,
                $weight,
                $height,
                $gender,
                $bodyType,
                $dietType,
                $goal,
                $budget
            )
        );
    }
}
