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

use Pratech\Return\Api\Data\HistoryInterface;
use Pratech\Return\Model\OptionSource\EventInitiator;
use Pratech\Return\Model\OptionSource\EventType;

class ProcessMessage
{
    /**
     * @var string[]
     */
    public $events = [
        EventType::RMA_CREATED => '%1 %2 created new RMA',
        EventType::STATUS_CHANGED_FROM_CLICKPOST => 'Status automatically changed from "%3" to "%4"',
        EventType::TRACKING_NUMBER_ADDED => '%1 %2 added tracking number %3 %4',
        EventType::TRACKING_NUMBER_DELETED => '%1 %2 deleted tracking number %3 %4',
        EventType::CUSTOMER_CLOSED_RMA => 'Customer closed RMA',
        EventType::SYSTEM_CHANGED_STATUS => 'Status changed from "%3" to "%4"',
        EventType::SYSTEM_CHANGED_MANAGER => 'Manager changed from "%3" to "%4"',
        EventType::MANAGER_CHANGED_REFUND_STATUS => 'Manager changed from "%3" to "%4".'
    ];

    /**
     * @var string[]
     */
    public $saveEvents = [
        'status' => 'Status changed from "%1" to "%2". ',
        'refund_status' => 'Refund Status changed from "%1" to "%2". ',
        'manager' => 'Manager changed from "%1" to "%2". ',
        'note' => 'Note changed from "%1" to "%2"',
        'item-changed' => 'Item "%1 %2" changed:',
        'state' => '- state from "%1" to "%2"',
        'qty' => '- qty from "%1" to "%2"',
        'reason' => '- reason from "%1" to "%2"',
        'splited' => 'Item "%1 %2" splited.- state: %3- qty: %4- reason: %5'
    ];

    public function execute(HistoryInterface $event)
    {
        $data = $event->getEventData();

        array_unshift($data, $event->getEventInitiatorName());
        $who = __('System');
        switch ($event->getEventInitiator()) {
            case EventInitiator::MANAGER:
                $who = __('Manager');
                break;
            case EventInitiator::CUSTOMER:
                $who = __('Customer');
                break;
        }
        array_unshift($data, $who);

        switch ($event->getEventType()) {
            case EventType::MANAGER_SAVED_RMA:
                $event->setMessage($this->getMessageForSavedRmaByManager($data));
                break;
            default:
                if (isset($this->events[$event->getEventType()])) {
                    $event->setMessage(__($this->events[$event->getEventType()], ...$data));
                } else {
                    $event->setMessage('');
                }
                break;
        }

        return $event;
    }

    public function getMessageForSavedRmaByManager($data)
    {
        $message = '';
        if (isset($data['before']) && isset($data['after'])) {

            $before = $data['before'];
            $after = $data['after'];
            if (isset($before['status']) && isset($after['status'])) {
                $message .= __($this->saveEvents['status'], $before['status'], $after['status']) . "\n";
            }
            if (isset($before['refund_status']) && isset($after['refund_status'])) {
                $message .= __($this->saveEvents['refund_status'], $before['refund_status'], $after['refund_status']) . "\n";
            }

            if (isset($before['manager']) && isset($after['manager'])) {
                $message .= __($this->saveEvents['manager'], $before['manager'], $after['manager']) . "\n";
            }

            if (isset($before['note']) && isset($after['note'])) {
                $message .= __($this->saveEvents['note'], $before['note'], $after['note']) . "\n";
            }

            $message .= "\n";

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $message .= __($this->saveEvents['item-changed'], $item['name'], $item['sku']) . "\n";
                    foreach (['state', 'qty', 'reason'] as $itemParam) {
                        if (!empty($item['before'][$itemParam]) && !empty($item['after'][$itemParam])) {
                            $message .= __(
                                $this->saveEvents[$itemParam],
                                $item['before'][$itemParam],
                                $item['after'][$itemParam]
                            ) . "\n";
                        }
                    }
                }
                $message .= "\n";
            }

            if (!empty($data['splited'])) {
                foreach ($data['splited'] as $item) {
                    $message .= __(
                        $this->saveEvents['splited'],
                        $item['name'],
                        $item['sku'],
                        $item['state'],
                        $item['qty'],
                        $item['reason'],
                    ) . "\n";
                }
            }
        }

        return $message;
    }
}
