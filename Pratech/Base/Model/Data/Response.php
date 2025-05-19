<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Model\Data;

class Response
{
    /**
     * Format the response of APIs
     *
     * @param int $status
     * @param string $message
     * @param string $resource
     * @param array $data
     * @return array
     */
    public function getResponse(int $status, string $message, string $resource, array $data)
    {
        return [
            'status' => $status,
            'message' => $message,
            'resource' => $resource,
            'data' => $data
        ];
    }
}
