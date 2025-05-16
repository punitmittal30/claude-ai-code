<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Return\Api\Data;

/**
 * Interface ReasonInterface
 */
interface ReasonInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const REASON_ID = 'reason_id';
    public const TITLE = 'title';
    public const STATUS = 'status';
    public const POSITION = 'position';
    public const LABEL = 'label';
    public const IS_DELETED = 'is_deleted';

    /**
     * @param int $reasonId
     *
     * @return $this
     */
    public function setReasonId($reasonId);

    /**
     * @return int
     */
    public function getReasonId();

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position);

    /**
     * @return int
     */
    public function getPosition();

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param bool $isDeleted
     *
     * @return $this
     */
    public function setIsDeleted($isDeleted);

    /**
     * @return bool
     */
    public function getIsDeleted();
}
