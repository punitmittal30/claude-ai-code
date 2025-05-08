<?php
namespace Pratech\Catalog\Ui\DataProvider\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class LinkedConfigurableProductDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;

    /**
     * Configurable Product Data Provider
     *
     * @param string            $name
     * @param string            $primaryFieldName
     * @param string            $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface  $request
     * @param array             $meta
     * @param array             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get Configurable Product Data
     *
     * @return array
     */
    public function getData(): array
    {
        $currentProductId = (int)$this->request->getParam('id');
        
        $this->collection->addAttributeToFilter('type_id', 'configurable')
            ->addAttributeToSelect(['name', 'sku'])
            ->addFieldToFilter('entity_id', ['neq' => $currentProductId]);

        return [
            'totalRecords' => $this->collection->getSize(),
            'items' => array_values($this->collection->toArray())
        ];
    }
}