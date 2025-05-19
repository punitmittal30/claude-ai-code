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

namespace Pratech\Return\Model\Reason\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Return\Api\Data\ReasonInterface;
use Pratech\Return\Model\Reason\ReasonFactory;
use Pratech\Return\Model\Reason\ResourceModel\CollectionFactory;

class Form extends AbstractDataProvider
{
    /**
     * @var array
     */
    private $loadedData = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param ReasonFactory $reasonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        private CollectionFactory      $collectionFactory,
        private ReasonFactory          $reasonFactory,
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
        $this->getCollection()->addFieldToSelect(ReasonInterface::REASON_ID);
        $data = parent::getData();

        if (isset($data['items'][0])) {
            $reasonId = $data['items'][0][ReasonInterface::REASON_ID];
            $reason = $this->reasonFactory->create()->load($reasonId);
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
