<?php
/**
 * Pratech_ClickPostIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ClickPostIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ClickPostIntegration\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\ClickPostIntegration\Api\ClickPostInterface;

class ClickPost implements ClickPostInterface
{
    /**
     * Click Post Constructor
     *
     * @param ClickPostConnection $clickPostConnection
     * @param Response $response
     */
    public function __construct(
        private ClickPostConnection $clickPostConnection,
        private Response $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getEstimatedDeliveryDate(string $destination): array
    {
        return $this->response->getResponse(
            '200',
            'success',
            'order',
            $this->clickPostConnection->getEstimatedDeliveryDate($destination)
        );
    }
}
