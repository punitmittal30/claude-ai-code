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

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Actions extends Column
{
    const UPDATE_COD_STATUS_URL_PATH = 'codrestriction/codordercounter/updatecodstatus';

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface       $context,
        UiComponentFactory     $uiComponentFactory,
        protected UrlInterface $urlBuilder,
        array                  $components = [],
        array                  $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as & $item) {
            if (isset($item['entity_id']) && isset($item['customer_id'])) {
                $customerId = (int)$item['customer_id'];
                $isCodDisabled = (bool)$item['is_cod_disabled'];

                $item[$this->getData('name')]['toggle_cod'] = [
                    'href' => $this->urlBuilder->getUrl(
                        self::UPDATE_COD_STATUS_URL_PATH,
                        [
                            'customer_id' => $customerId,
                            'status' => $isCodDisabled ? 0 : 1
                        ]
                    ),
                    'label' => $isCodDisabled ? __('Enable COD') : __('Disable COD'),
                    'confirm' => [
                        'title' => $isCodDisabled ? __('Enable COD') : __('Disable COD'),
                        'message' => $isCodDisabled
                            ? __('Are you sure you want to enable COD for this customer?')
                            : __('Are you sure you want to disable COD for this customer?')
                    ]
                ];
            }
        }
        return $dataSource;
    }
}
