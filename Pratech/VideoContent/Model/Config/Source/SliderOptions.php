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

namespace Pratech\VideoContent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\VideoContent\Model\ResourceModel\Slider\Collection;
use Pratech\VideoContent\Model\ResourceModel\Slider\CollectionFactory;

/**
 * Slider Options class to get sliders
 */
class SliderOptions implements OptionSourceInterface
{
    /**
     * Constructor
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options[] = ['label' => 'Default', 'value' => ''];
        $collection = $this->collectionFactory
            ->create()
            ->addFieldToSelect('slider_id')
            ->addFieldToSelect('name');

        foreach ($collection as $item) {
            $options[] = [
                'value' => $item->getId(),
                'label' => $item->getName()
            ];
        }

        return $options;
    }
}
