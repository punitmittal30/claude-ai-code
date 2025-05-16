<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class AssociatedVideos For fetching videos associated with slider to show in grid.
 */
class AssociatedVideos extends Column
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * AssociatedVideos constructor.
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
                    $videoNames = '';
                    $connection = $this->resource->getConnection();
                    /* Code for video slides */
                    $select = $connection
                        ->select()
                        ->from(['us' => 'video_entity'], ['name'])
                        ->join(['ubs' => 'video_slider_mapping'], 'us.video_id = ubs.video_id', [])
                        ->where("ubs.slider_id = " . $item['slider_id']);

                    $videoCollection = $connection->fetchAll($select);
                    $videoList = [];
                    foreach ($videoCollection as $video) {
                        $videoList[] = $video['name'];
                    }

                    if (!empty($videoList)) {
                        $videoNames = implode(", ", $videoList);
                    }
                    /* Code for video slides */

                    $item[$this->getData('name')] = $videoNames;
                }
            }
        }

        return $dataSource;
    }
}
