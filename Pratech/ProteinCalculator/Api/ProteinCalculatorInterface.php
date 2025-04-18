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

namespace Pratech\ProteinCalculator\Api;

interface ProteinCalculatorInterface
{
    /**
     * Calculate protein needs based on user data.
     *
     * @param int    $age
     * @param int    $weight
     * @param string $height
     * @param string $gender
     * @param string $bodyType
     * @param string $dietType
     * @param string $goal
     * @param string $budget
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProteinData(
        int     $age,
        int     $weight,
        string  $height,
        string  $gender,
        string  $bodyType,
        string  $dietType,
        string  $goal,
        string  $budget
    ): array;
}
