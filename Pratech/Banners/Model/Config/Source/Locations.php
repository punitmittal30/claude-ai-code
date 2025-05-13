<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Locations implements OptionSourceInterface
{

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options[] = ['label' => 'Default', 'value' => ''];
        return array_merge($options, $this->getOptions());
    }

    /**
     * Get Options
     *
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            [
                'label' => __('Home Page Top'),
                'value' => __('home_page_top')
            ], [
                'label' => __('Home Page Middle'),
                'value' => __('home_page_middle')
            ], [
                'label' => __('Home Page Bottom'),
                'value' => __('home_page_bottom')
            ], [
                'label' => __('Home Page Top Below'),
                'value' => __('home_page_top_below')
            ], [
                'label' => __('Category Banner'),
                'value' => __('category_banner')
            ], [
                'label' => __('Search Banner'),
                'value' => __('search_banner')
            ]
        ];
    }
}
