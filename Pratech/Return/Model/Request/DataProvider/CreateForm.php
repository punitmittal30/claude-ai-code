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

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\RequestInterface as HttpRequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Return\Api\CreateReturnProcessorInterface;
use Pratech\Return\Api\Data\RequestInterface;
use Pratech\Return\Api\Data\RequestItemInterface;
use Pratech\Return\Model\OptionSource\NoReturnableReasons;
use Pratech\Return\Model\OptionSource\Reason as ReasonOptions;
use Pratech\Return\Model\Order\OrderItemImage;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory;

class CreateForm extends AbstractDataProvider
{
    protected $returnOrder;

    /**
     * @param CreateReturnProcessorInterface $createReturnProcessor
     * @param HttpRequestInterface $request
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $url
     * @param GroupRepositoryInterface $groupRepository
     * @param AddressRenderer $addressRenderer
     * @param OrderItemImage $orderItemImage
     * @param ReasonOptions $reasonOptions
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        private CreateReturnProcessorInterface $createReturnProcessor,
        private HttpRequestInterface           $request,
        private CollectionFactory              $collectionFactory,
        private UrlInterface                   $url,
        private GroupRepositoryInterface       $groupRepository,
        private AddressRenderer                $addressRenderer,
        private OrderItemImage                 $orderItemImage,
        private ReasonOptions                  $reasonOptions,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array                                  $meta = [],
        array                                  $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $order = $this->returnOrder->getOrder();
        $data[null]['return_items'] = [];

        foreach ($this->returnOrder->getItems() as $item) {
            $tempItem = clone $item;
            $itemData = $tempItem->getData();
            unset($itemData['product_item']);
            unset($itemData['item']);

            $itemData = [
                RequestItemInterface::REQUEST_ITEM_ID => $item->getItem()->getItemId(),
                'name' => $item->getItem()->getName(),
                'sku' => $item->getItem()->getSku(),
                'url' => $this->url->getUrl(
                    'catalog/product/edit',
                    ['id' => $item->getItem()->getProductId()]
                ),
                'shipped_qty' => (double)$item->getItem()->getQtyShipped(),
                'refunded_qty' => (double)$item->getItem()->getQtyRefunded(),
                RequestItemInterface::REQUEST_QTY => (double)$item->getAvailableQty(),
                RequestItemInterface::QTY => (double)$item->getAvailableQty(),
                RequestItemInterface::ORDER_ITEM_ID => $item->getItem()->getItemId(),
                RequestItemInterface::ITEM_STATUS => 0,
                'is_decimal' => (bool)$item->getItem()->getIsQtyDecimal(),
                'image' => $this->orderItemImage->getUrl($item->getItem()->getItemId()),
                'is_returnable' => $item->isReturnable(),
                'no_returnable_reason' => $item->getNoReturnableReason(),
                RequestItemInterface::REASON_ID => '',
            ];

            if (!$item->isReturnable() && ($item->getNoReturnableReason() === NoReturnableReasons::ALREADY_RETURNED)) {
                $itemData['previous_requests'] = [];

                foreach ($item->getNoReturnableData() as $request) {
                    $itemData['previous_requests'][] = [
                        'url' => $this->url->getUrl(
                            'return/request/view',
                            ['request_id' => $request[RequestInterface::REQUEST_ID]]
                        ),
                        'label' => '#' . str_pad(
                            $request[RequestInterface::REQUEST_ID],
                            9,
                            '0',
                            STR_PAD_LEFT
                        )
                    ];
                }
            }

            $data[null]['return_items'][][] = $itemData;
        }

        $customerGroup = '';

        try {
            $customerGroup = $this->groupRepository->getById($order->getCustomerGroupId())->getCode();
        } catch (NoSuchEntityException $e) {
            $customerGroup = __('Customer group with specified ID %1 not found.', $order->getCustomerGroupId());
        }

        $data[null][RequestInterface::ORDER_ID] = $order->getEntityId();
        $data[null]['information'] = [
            'order' => [
                'entity_id' => $order->getEntityId(),
                'increment_id' => '#' . $order->getIncrementId(),
                'created' => $order->getCreatedAt(),
                'status' => $order->getStatus(),
                'link' => $this->url->getUrl(
                    'sales/order/view',
                    ['order_id' => $order->getEntityId()]
                )
            ],
            'customer' => [
                'name' => $order->getBillingAddress()->getFirstname() . ' '
                    . $order->getBillingAddress()->getLastname(),
                'address' => $this->addressRenderer->format($order->getBillingAddress(), 'html'),
                'email' => $order->getBillingAddress()->getEmail(),
                'customer_group' => $customerGroup
            ]
        ];
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();
        $this->returnOrder = $this->createReturnProcessor->process(
            $this->request->getParam('order_id'),
            true
        );

        //Items To Return Meta
        $meta['rma_return_order']['arguments']['data']['config']['header'] = [
            __('Product'),
            __('Return Reason'),
            __('Shipped QTY'),
            __('Refunded QTY'),
            __('Return QTY'),
            __('Approved')
        ];
        $meta['rma_return_order']['arguments']['data']['config']['reasons'] =
            $this->reasonOptions->toOptionArray();

        return $meta;
    }
}
