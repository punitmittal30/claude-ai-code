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

namespace Pratech\Return\Model\RejectReason\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Return\Api\Data\RejectReasonInterface;
use Pratech\Return\Model\RejectReason\RejectReasonFactory;
use Pratech\Return\Model\RejectReason\ResourceModel\CollectionFactory;

class Form extends AbstractDataProvider
{
    /**
     * @var array
     */
    private $loadedData = [];

    /**
     * @param CollectionFactory   $collectionFactory
     * @param RejectReasonFactory $rejectReasonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        private CollectionFactory      $collectionFactory,
        private RejectReasonFactory    $rejectReasonFactory,
        private DataPersistorInterface $dataPersistor,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array                          $meta = [],
        array                          $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }
        $this->getCollection()->addFieldToSelect(RejectReasonInterface::REASON_ID);
        $data = parent::getData();

        if (isset($data['items'][0])) {
            $reasonId = $data['items'][0][RejectReasonInterface::REASON_ID];
            $reason = $this->rejectReasonFactory->create()->load($reasonId);
            $this->loadedData[$reasonId] = $reason->getData();
        }
        $data = $this->dataPersistor->get('reason_data');

        if (!empty($data)) {
            $reasonId = $data['reason_id'] ?? null;
            $this->loadedData[$reasonId] = $data;
            $this->dataPersistor->clear('reason_data');
        }
        return $this->loadedData;
    }
}
