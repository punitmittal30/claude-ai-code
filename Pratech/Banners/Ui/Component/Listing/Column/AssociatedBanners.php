<?php

namespace Pratech\Banners\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class AssociatedBanners For fetching banners associated with slider to show in grid.
 */
class AssociatedBanners extends Column
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * BannerSlides constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ResourceConnection $resource
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ResourceConnection $resource,
        array $components = [],
        array $data = []
    ) {
        $this->resource = $resource;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['slider_id'])) {
                    $bannerNames = '';
                    $connection = $this->resource->getConnection();
                    /* Code for banner slides */
                    $select = $connection
                        ->select()
                        ->from(['us' => 'pratech_banner'], ['name'])
                        ->join(['ubs' => 'pratech_slider_banner'], 'us.banner_id = ubs.banner_id', [])
                        ->where("ubs.slider_id = " . $item['slider_id']);

                    $bannerCollection = $connection->fetchAll($select);
                    $bannerList = [];
                    foreach ($bannerCollection as $banner) {
                        $bannerList[] = $banner['name'];
                    }

                    if (!empty($bannerList)) {
                        $bannerNames = implode(", ", $bannerList);
                    }
                    /* Code for banner slides */

                    $item[$this->getData('name')] = $bannerNames;
                }
            }
        }

        return $dataSource;
    }
}
