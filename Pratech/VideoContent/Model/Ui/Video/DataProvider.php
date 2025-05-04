<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Model\Ui\Video;

use Exception;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\VideoContent\Model\Video;
use Pratech\VideoContent\Model\ResourceModel\Video\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Video $videoModel
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private Video $videoModel,
        private StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if (!$this->loadedData) {
            foreach ($this->collection->getItems() as $video) {
                $data = $video->getData();

                if (isset($data['url']) && !empty($data['url']) && is_string($data['url'])) {
                    try {
                        $mediaUrl = $this->storeManager->getStore()
                            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                    } catch (Exception $e) {
                        $mediaUrl = "";
                    }

                    $relativeFilePath = ltrim(str_replace($mediaUrl, '', $data['url']), '/');
                    $fullFilePath = BP . '/pub/media/' . $relativeFilePath;

                    $size = file_exists($fullFilePath) ? filesize($fullFilePath) : 0;

                    $data['url'] = [
                        [
                            'name' => basename($relativeFilePath),
                            'size' => $size,
                            'url' => $mediaUrl . $relativeFilePath,
                            'file' => $relativeFilePath,
                            'type' => 'video'
                        ]
                    ];
                    $data['cities'] = explode(',', $video->getCities());

                    $sliderId = $this->videoModel->getSliderId($data['video_id']);
                    $data['assign_to_slider'] = $sliderId;
                }

                $this->loadedData[$video->getId()] = $data;
            }
        }
        return $this->loadedData;
    }
}
