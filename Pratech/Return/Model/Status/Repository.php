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

namespace Pratech\Return\Model\Status;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Api\Data\StatusInterfaceFactory;
use Pratech\Return\Api\StatusRepositoryInterface;
use Pratech\Return\Model\OptionSource\State;

class Repository implements StatusRepositoryInterface
{
    /**
     * @var StatusInterface[]
     */
    private $statuses;

    public function __construct(
        private StatusInterfaceFactory          $statusFactory,
        private ResourceModel\Status            $statusResource,
        private ResourceModel\CollectionFactory $collectionFactory,
        private State                           $state
    ) {
    }

    /**
     * @inheritdoc
     */
    public function clearDeleted()
    {
        $statusCollection = $this->collectionFactory->create();
        $statusCollection->addFieldToFilter(StatusInterface::IS_DELETED, 1);

        try {
            foreach ($statusCollection->getItems() as $status) {
                $statusId = $status->getStatusId();
                $this->statusResource->delete($status);
                unset($this->statuses[$statusId]);
            }
        } catch (Exception $e) {
            if ($statusId) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove status with ID %1. Error: %2',
                        [$statusId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove status. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(StatusInterface $status)
    {
        if ($status->isInitial()) {
            throw new LocalizedException(__('Can\'t delete initial status.'));
        }
        if ($this->isLastState($status)) {
            $states = $this->state->toArray();

            throw new LocalizedException(
                __('Can\'t delete status because it is the only status of state "%1".', $states[$status->getState()])
            );
        }
        $status->setIsDeleted(true);
        $this->save($status);
        unset($this->statuses[$status->getStatusId()]);

        return true;
    }

    /**
     * @param StatusInterface $status
     *
     * @return bool
     */
    private function isLastState(StatusInterface $status)
    {
        $collection = $this->getEmptyStatusCollection();
        $collection->addFieldToFilter(StatusInterface::STATE, $status->getState())->addNotDeletedFilter();

        return $collection->count() <= 1;
    }

    public function getEmptyStatusCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function save(StatusInterface $status)
    {
        try {
            if (!$status->isInitial()) {
                $initialStatus = $this->collectionFactory->create()
                    ->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
                    ->addFieldToSelect(StatusInterface::STATUS_ID)
                    ->getData();

                if (!$initialStatus || ($status->getStatusId()
                        && $status->getStatusId() == $initialStatus[0][StatusInterface::STATUS_ID])
                ) {
                    throw new LocalizedException(__('There is no initial status.'));
                }
            }

            if ($status->isInitial() && !$status->isEnabled()) {
                throw new LocalizedException(__('Initial status can\'t be disabled.'));
            }

            if ($status->getStatusId()) {
                if (!$status->isEnabled() && $this->isLastState($status)) {
                    $states = $this->state->toArray();

                    throw new LocalizedException(
                        __(
                            'Can\'t disable status because it is the only status of state "%1".',
                            $states[$status->getState()]
                        )
                    );
                }
            }
            if ($status->getStatusId()) {
                $status = $this->getById($status->getStatusId())->addData($status->getData());
            }

            if ($status->getState() === State::CANCELED) {
                $status->setIsEnabled(true)->setIsInitial(false);
            }

            $this->statusResource->save($status);
            if ($status->getAutoEvent()) {
                $this->statusResource->unsetAutoEvent(
                    $status->getAutoEvent(),
                    $status->getState(),
                    $status->getStatusId()
                );
            }
            if ($status->isInitial()) {
                $this->statusResource->unsetPreviousInitialStatus($status->getStatusId());
            }

            unset($this->statuses[$status->getStatusId()]);
        } catch (Exception $e) {
            if ($status->getStatusId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save status with ID %1. Error: %2',
                        [$status->getStatusId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new status. Error: %1', $e->getMessage()));
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function getById($statusId)
    {
        if (!isset($this->statuses[$statusId])) {
            /**
             * @var StatusInterface $status
             */
            $status = $this->statusFactory->create();
            $this->statusResource->load($status, $statusId);
            if (!$status->getStatusId()) {
                throw new NoSuchEntityException(__('Status with specified ID "%1" not found.', $statusId));
            }

            $this->statuses[$statusId] = $status;
        }

        return $this->statuses[$statusId];
    }

    /**
     * @inheritdoc
     */
    public function deleteById($statusId)
    {
        $status = $this->getById($statusId);

        $this->delete($status);
    }

    /**
     * @inheritdoc
     */
    public function getInitialStatusId()
    {
        return (int)$this->collectionFactory->create()->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
            ->fetchItem()
            ->getStatusId();
    }

    /**
     * @inheritdoc
     */
    public function getCancelStatusId()
    {
        return (int)$this->collectionFactory->create()
            ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)
            ->fetchItem()
            ->getStatusId();
    }

    /**
     * @return StatusInterface
     */
    public function getEmptyStatusModel()
    {
        return $this->statusFactory->create();
    }
}
