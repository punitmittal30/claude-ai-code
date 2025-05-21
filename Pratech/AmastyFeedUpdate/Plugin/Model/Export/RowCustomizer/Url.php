<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Plugin\Model\Export\RowCustomizer;

use Amasty\Feed\Model\Export\Product as ExportProduct;
use Amasty\Feed\Model\Export\RowCustomizer\Url as FeedUrl;

/**
 * Plugin to Update Product Url for feed
 */
class Url extends FeedUrl
{

    /**
     * {@inheritdoc}
     */
    public function addData($dataRow, $productId)
    {
        $result = parent::addData($dataRow, $productId);

        $result['amasty_custom_data'][ExportProduct::PREFIX_URL_ATTRIBUTE] = [
            'short' => $this->export->getProductBaseUrl().'product/'.$dataRow['url_key'],
            'with_category' => $this->export->getProductBaseUrl().'product/'.$dataRow['url_key']

        ];
        return $result;
    }
}
