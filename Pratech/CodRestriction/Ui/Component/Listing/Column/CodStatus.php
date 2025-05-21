<?php
/**
 * Pratech_CodRestriction
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CodRestriction\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class CodStatus extends Column
{
    /**
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['is_cod_disabled'])) {
                    $item['cod_allowed'] = $item['is_cod_disabled']
                        ? '<span style="color:red;font-weight:bold;">Disabled</span>'
                        : '<span style="color:green;font-weight:bold;">Enabled</span>';
                }
            }
        }
        return $dataSource;
    }
}
