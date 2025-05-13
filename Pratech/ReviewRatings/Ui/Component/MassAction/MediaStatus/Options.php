<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ReviewRatings\Ui\Component\MassAction\MediaStatus;

use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Pratech\ReviewRatings\Model\Config\Source\MediaStatus;

/**
 * Media Status Options
 */
class Options implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $urlPath;

    /**
     * @var string
     */
    protected $paramName;

    /**
     * @var array
     */
    protected $additionalData = [];

    /**
     * Media Status Options constructor
     *
     * @param UrlInterface $urlBuilder
     * @param MediaStatus $mediaStatus
     * @param array $data
     */
    public function __construct(
        protected UrlInterface $urlBuilder,
        protected MediaStatus $mediaStatus,
        protected array $data = []
    ) {
        $this->data = $data;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get options
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        if ($this->options === null) {
            $options = $this->mediaStatus->toOptionArray();
            $this->prepareData();
            foreach ($options as $optionCode) {
                $this->options[$optionCode['value']] = [
                    'type' => 'change_status_' . $optionCode['value'],
                    'label' => __($optionCode['label']),
                    '__disableTmpl' => true
                ];

                if ($this->urlPath && $this->paramName) {
                    $this->options[$optionCode['value']]['url'] = $this->urlBuilder->getUrl(
                        $this->urlPath,
                        [$this->paramName => $optionCode['value']]
                    );
                }

                $this->options[$optionCode['value']] = array_merge_recursive(
                    $this->options[$optionCode['value']],
                    $this->additionalData
                );
            }

            $this->options = array_values($this->options);
        }

        return $this->options;
    }

    /**
     * Prepare Data
     *
     * @return void
     */
    protected function prepareData()
    {
        foreach ($this->data as $key => $value) {
            switch ($key) {
                case 'urlPath':
                    $this->urlPath = $value;
                    break;
                case 'paramName':
                    $this->paramName = $value;
                    break;
                case 'confirm':
                    foreach ($value as $messageName => $message) {
                        $this->additionalData[$key][$messageName] = (string)new Phrase($message);
                    }
                    break;
                default:
                    $this->additionalData[$key] = $value;
                    break;
            }
        }
    }
}
