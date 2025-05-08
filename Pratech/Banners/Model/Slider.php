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

namespace Pratech\Banners\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Slider Model class
 */
class Slider extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'slider';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Slider::class);
    }

    /**
     * Get Banner IDs
     *
     * @return array
     */
    public function getBannersIds()
    {
        if (!$this->hasData('banner_id')) {
            $ids = $this->getResource()->getBannersIds($this);

            $this->setData('banner_id', $ids);
        }

        return (array) $this->getData('banner_id');
    }
}
