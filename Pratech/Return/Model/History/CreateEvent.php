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

namespace Pratech\Return\Model\History;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\CouldNotSaveException;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\HistoryRepositoryInterface;
use Pratech\Return\Model\OptionSource\EventInitiator;

class CreateEvent
{
    /**
     * @param HistoryRepositoryInterface $historyRepository
     * @param Session $authSession
     */
    public function __construct(
        private HistoryRepositoryInterface $historyRepository,
        private Session                    $authSession
    ) {
    }

    /**
     * @param int $eventType
     * @param RequestInterface $request
     * @param int $initiator
     * @param array $additionalData
     * @return boolean
     */
    public function execute(
        int              $eventType,
        RequestInterface $request,
        int              $initiator,
        $additionalData = []
    ): bool {
        try {
            $event = $this->historyRepository->getEmptyEventModel()
                ->setRequestId($request->getRequestId())
                ->setRequestStatusId($request->getStatus())
                ->setEventType($eventType)
                ->setEventInitiator($initiator)
                ->setEventData($additionalData);

            switch ($initiator) {
                case EventInitiator::MANAGER:
                    $userName = __('CLI');
                    $user = $this->authSession->getUser();
                    if ($user !== null) {
                        $userName = $user->getName();
                    }
                    $event->setEventInitiatorName($userName);
                    break;
                case EventInitiator::CUSTOMER:
                    $event->setEventInitiatorName($request->getCustomerName());
                    break;
            }

            $this->historyRepository->create($event);
        } catch (CouldNotSaveException $exception) {
            return false;
        }
        return true;
    }
}
