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

use Pratech\Return\Api\Data\StatusStoreInterface;

/**
 * Interface StatusInterface
 */
interface StatusInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const STATUS_ID = 'status_id';
    public const IS_ENABLED = 'is_enabled';
    public const IS_INITIAL = 'is_initial';
    public const AUTO_EVENT = 'auto_event';
    public const STATE = 'state';
    public const GRID = 'grid';
    public const PRIORITY = 'priority';
    public const TITLE = 'title';
    public const LABEL = 'label';
    public const IS_DELETED = 'is_deleted';

    /**
     * @param int $statusId
     *
     * @return $this
     */
    public function setStatusId($statusId);

    /**
     * @return int
     */
    public function getStatusId();

    /**
     * @param bool $isEnabled
     *
     * @return $this
     */
    public function setIsEnabled($isEnabled);

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @param bool $isInitial
     *
     * @return $this
     */
    public function setIsInitial($isInitial);

    /**
     * @return bool
     */
    public function isInitial();

    /**
     * @param int $state
     *
     * @return $this
     */
    public function setState($state);

    /**
     * @return int
     */
    public function getState();

    /**
     * @param int $grid
     *
     * @return $this
     */
    public function setGrid($grid);

    /**
     * @return int
     */
    public function getGrid();

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority);

    /**
     * @return int
     */
    public function getPriority();

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
     * @return StatusStoreInterface
     */
    public function setIsDeleted($isDeleted);

    /**
     * @return bool
     */
    public function getIsDeleted();
}
