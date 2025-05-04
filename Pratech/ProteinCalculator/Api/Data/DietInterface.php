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

namespace Pratech\ProteinCalculator\Api\Data;

interface DietInterface
{
    public const ENTITY_ID = 'entity_id';
    public const DIET_TYPE = 'diet_type';
    public const DIET = 'diet';
    public const BUDGET = 'budget';
    public const PRODUCT_ID = 'product_id';

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getId();

    /**
     * Set entity ID
     *
     * @param int $id
     * @return \Pratech\ProteinCalculator\Api\Data\DietInterface
     */
    public function setId($id);

    /**
     * Get Diet Type
     *
     * @return string
     */
    public function getDietType();

    /**
     * Set Diet Type
     *
     * @param string $dietType
     * @return \Pratech\ProteinCalculator\Api\Data\DietInterface
     */
    public function setDietType($dietType);

    /**
     * Get Diet
     *
     * @return string
     */
    public function getDiet();

    /**
     * Set Diet
     *
     * @param string $diet
     * @return \Pratech\ProteinCalculator\Api\Data\DietInterface
     */
    public function setDiet($diet);

    /**
     * Get Budget
     *
     * @return string
     */
    public function getBudget();

    /**
     * Set Budget
     *
     * @param string $budget
     * @return \Pratech\ProteinCalculator\Api\Data\DietInterface
     */
    public function setBudget($budget);

    /**
     * Get Product Id
     *
     * @return string
     */
    public function getProductId();

    /**
     * Set Product Id
     *
     * @param string $productId
     * @return \Pratech\ProteinCalculator\Api\Data\DietInterface
     */
    public function setProductId($productId);
}
