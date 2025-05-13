<?php

namespace Pratech\Banners\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * ImageThumbnail to show thumbnail image in slider grid
 */
class ImageThumbnail extends Column
{
    /**
     * ALT FIELD CONSTANT
     */
    public const ALT_FIELD = 'name';

    /**
     * @var string
     */
    protected $subDir = 'banner/feature';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
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
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $filename = $item[$fieldName];
                    if ($filename) {
                        $filename = str_replace('/', '', $filename);
                    }
                    $item[$fieldName . '_src'] = $this->getImageUrl($filename);
                    $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $filename;
                    $item[$fieldName . '_orig_src'] = $this->getImageUrl($filename);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get Image URL
     *
     * @param $fileName
     * @return string
     */
    public function getImageUrl($fileName)
    {
        return $this->urlBuilder->getBaseUrl(
            ['_type' => UrlInterface::URL_TYPE_MEDIA]
        ) . $this->subDir . '/' . $fileName;
    }

    /**
     * Get Image Alt Text
     *
     * @param array $row
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return $row[$altField] ?? null;
    }
}
