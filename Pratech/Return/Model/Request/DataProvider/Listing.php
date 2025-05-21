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

namespace Pratech\Return\Model\Request\DataProvider;

use Magento\Backend\Model\Session;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface as AppRequest;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as ShipmentStatusCollectionFactory;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Model\Request\ResourceModel\Grid\Collection;
use Pratech\Return\Model\Request\ResourceModel\Grid\CollectionFactory;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;
use Zend_Db_Expr;

class Listing extends AbstractDataProvider
{

    /**
     * @var Session
     */
    private $session;

    /**
     * @var SearchCriteria
     */
    private $searchCriteria;

    /**
     * @var bool
     */
    private $isManageGrid;

    public function __construct(
        CollectionFactory               $collectionFactory,
        AppRequest                      $request,
        StatusCollectionFactory         $statusCollectionFactory,
        Session                         $session,
        SearchCriteriaBuilder           $searchCriteriaBuilder,
        ShipmentStatusCollectionFactory $shipmentStatusCollectionFactory,
                                        $name,
                                        $primaryFieldName,
                                        $requestFieldName,
        array                           $meta = [],
        array                           $data = []
    )
    {
        $this->searchCriteria = $searchCriteriaBuilder->create()->setRequestName($name);
        $this->collection = $collectionFactory->create()->setSearchCriteria($this->searchCriteria)->addLeadTime();
        $statusIds = [];
        $statusCollection = $statusCollectionFactory->create();
        $statusCollection->addFieldToSelect(StatusInterface::STATUS_ID);

        $shipmentStatusCollection = $shipmentStatusCollectionFactory->create();
        $shipmentStatusCollection->addFieldToSelect('status_id');

        if ($request->getActionName() === 'manage') {
            $this->isManageGrid = true;
        }
        switch ($request->getParam('grid', 'pending')) {
            case 'pending':
                $shipmentStatusCollection->addFieldToFilter('status_code', 'return_pending');
                break;
            case 'archive':
                $shipmentStatusCollection->addFieldToFilter('status_code', ['in' => ['refund_completed', 'return_canceled']]);
                break;
            case 'order_view':
                $orderId = (int)$request->getParam(RequestInterface::ORDER_ID);
                $this->collection->addFieldToFilter(RequestInterface::ORDER_ID, $orderId);
                break;
            default:
                $shipmentStatusCollection->addFieldToFilter(
                    'status_code',
                    ['nin' => ['refund_completed', 'return_pending']]
                );
        }

        foreach ($shipmentStatusCollection->getData() as $status) {
            $statusIds[] = (int)$status['status_id'];
        }

        if (empty($statusIds)) {
            $statusIds[] = 9999999999999;
        }

        $this->collection->addFieldToFilter(
            [
                'main_table.' . RequestInterface::STATUS,
                'main_table.' . RequestInterface::REFUND_STATUS
            ],
            [
                ['in' => $statusIds],
                ['in' => $statusIds]
            ]
        );

        $this->collection->join(
            'sales_order',
            'main_table.' . RequestInterface::ORDER_ID . ' = sales_order.entity_id',
            [
                'sales_order.increment_id',
            ]
        );

        $data['config']['params']['order_id'] = $request->getParam(RequestInterface::ORDER_ID);

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->session = $session;
        $this->session->setreturnReturnUrl(null);
        $this->session->setreturnOriginalGrid(null);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return parent::getData();
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        switch ($filter->getField()) {
            case RequestInterface::STATUS:
                $filter->setField('main_table.' . RequestInterface::STATUS);
                break;
            case RequestInterface::CREATED_AT:
                $filter->setField('main_table.' . RequestInterface::CREATED_AT);
                break;
            case 'days':
                $filter->setField(new Zend_Db_Expr(Collection::DAYS_EXPRESSION));
                break;
        }

        parent::addFilter($filter);
    }

    /**
     * Returns search criteria
     *
     * @return SearchCriteria
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }
}
