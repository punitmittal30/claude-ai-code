<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Api;

/**
 * Cms Block Interface to power Cms APIs.
 */
interface CmsBlockInterface
{
    /**
     * Retrieve block.
     *
     * @param string $identifier
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getCmsBlockByIdentifier(string $identifier): array;
}
