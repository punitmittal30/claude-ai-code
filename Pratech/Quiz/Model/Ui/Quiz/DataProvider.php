<?php
/**
 * Pratech_Quiz
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Quiz
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Quiz\Model\Ui\Quiz;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Quiz\Model\ResourceModel\Quiz\CollectionFactory;

/**
 * Class DataProvider
 * Provides data for Quiz UI Component Form
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var array
     */
    private $loadedData = [];

    /**
     * Constructor
     *
     * @param string                 $name
     * @param string                 $primaryFieldName
     * @param string                 $requestFieldName
     * @param CollectionFactory      $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array                  $meta
     * @param array                  $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data for UI Component
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $quiz) {
            $this->loadedData[$quiz->getId()] = $quiz->getData();
        }

        $data = $this->dataPersistor->get('quiz');
        if (!empty($data)) {
            $quiz = $this->collection->getNewEmptyItem();
            $quiz->setData($data);
            $this->loadedData[$quiz->getId()] = $quiz->getData();
            $this->dataPersistor->clear('quiz');
        }

        return $this->loadedData;
    }
}
