<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Recurring\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Recurring\Api\SubscriptionManagementInterface;
use Pratech\Recurring\Helper\Recurring as RecurringHelper;

/**
 * Subscription Management for api.
 */
class SubscriptionManagement implements SubscriptionManagementInterface
{
    /**
     * Constant for RECURRING API RESOURCE
     */
    public const RECURRING_API_RESOURCE = 'recurring';

    /**
     * Constructor
     *
     * @param Response $response
     * @param RecurringHelper $recurringHelper
     */
    public function __construct(
        private Response $response,
        private RecurringHelper $recurringHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionFormData(int $orderId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RECURRING_API_RESOURCE,
            $this->recurringHelper->getSubscriptionFormData($orderId)
        );
    }

    /**
     * @inheritDoc
     */
    public function createSubscription(int $orderId, array $items): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RECURRING_API_RESOURCE,
            [
                'is_created' => $this->recurringHelper->createSubscription($orderId, $items)
            ]
        );
    }
}
