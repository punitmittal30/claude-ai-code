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

interface MultiplierInterface
{
    public const ENTITY_ID = 'entity_id';
    public const GENDER = 'gender';
    public const BODY_TYPE = 'body_type';
    public const GOAL = 'goal';
    public const MULTIPLIER = 'multiplier';

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
     * @return \Pratech\ProteinCalculator\Api\Data\MultiplierInterface
     */
    public function setId($id);

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender();

    /**
     * Set gender
     *
     * @param string $gender
     * @return \Pratech\ProteinCalculator\Api\Data\MultiplierInterface
     */
    public function setGender($gender);

    /**
     * Get body type
     *
     * @return string
     */
    public function getBodyType();

    /**
     * Set body type
     *
     * @param string $bodyType
     * @return \Pratech\ProteinCalculator\Api\Data\MultiplierInterface
     */
    public function setBodyType($bodyType);

    /**
     * Get goal
     *
     * @return string
     */
    public function getGoal();

    /**
     * Set goal
     *
     * @param string $goal
     * @return \Pratech\ProteinCalculator\Api\Data\MultiplierInterface
     */
    public function setGoal($goal);

    /**
     * Get multiplier
     *
     * @return float
     */
    public function getMultiplier();

    /**
     * Set multiplier
     *
     * @param float $multiplier
     * @return \Pratech\ProteinCalculator\Api\Data\MultiplierInterface
     */
    public function setMultiplier($multiplier);
}
