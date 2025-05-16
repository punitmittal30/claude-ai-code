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

namespace Pratech\Return\Model\Status\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface as HttpRequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Api\StatusRepositoryInterface;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory;

class Form extends AbstractDataProvider
{
    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var HttpRequestInterface
     */
    private $request;

    public function __construct(
        CollectionFactory         $collectionFactory,
        StatusRepositoryInterface $repository,
        DataPersistorInterface    $dataPersistor,
        HttpRequestInterface      $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array                     $meta = [],
        array                     $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->repository = $repository;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->getCollection()->addFieldToSelect(StatusInterface::STATUS_ID);
        $data = parent::getData();
        if (isset($data['items'][0])) {
            $statusId = $data['items'][0][StatusInterface::STATUS_ID];
            $status = $this->repository->getById($statusId);
            $this->loadedData[$statusId] = $status->getData();
        }
        $data = $this->dataPersistor->get('status_data');

        if (!empty($data)) {
            $statusId = isset($data['status_id']) ? $data['status_id'] : null;
            $this->loadedData[$statusId] = $data;
            $this->dataPersistor->clear('status_data');
        }

        return $this->loadedData;
    }
}
