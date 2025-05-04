<?php

namespace Pratech\Refund\Model\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderId extends Column
{
    /**
     * Url path to edit action.
     */
    public const URL_PATH_EDIT = 'sales/order/view';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * SlideActions constructor
     *
     * @param UrlInterface $urlBuilder
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        UrlInterface       $urlBuilder,
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        array              $components = [],
        array              $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id'])) {
                    $url = $this->urlBuilder->getUrl(static::URL_PATH_EDIT, ['order_id' => $item['order_id']]);
                    $link = '<a href="' . $url . '"">' . $item['order_id'] . '</a>';
                    $item['order_id'] = $link;
                }
            }
        }
        return $dataSource;
    }
}
