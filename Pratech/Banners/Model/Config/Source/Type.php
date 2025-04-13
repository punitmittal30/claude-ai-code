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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Type Class to provide source data to ui component.
 */
class Type implements OptionSourceInterface
{

    /**
     * Type Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getOptions();
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
                'label' => 'Default',
                'value' => ''
            ],
            [
                'label' => 'Home',
                'value' => $this->getSubElements('home')
            ],
            [
                'label' => 'Men',
                'value' => $this->getSubElements('men')
            ],
            [
                'label' => 'Women',
                'value' => $this->getSubElements('women')
            ],
            [
                'label' => 'Category',
                'value' => $this->getSubElements('category')
            ],
            [
                'label' => 'Brand',
                'value' => $this->getSubElements('brand')
            ]
        ];
    }

    /**
     * Get Sub Elements Of Configuration.
     *
     * @param string $identifier
     * @return array
     */
    public function getSubElements(string $identifier): array
    {
        $values = [];
        $types = $this->scopeConfig->getValue('banners/types/' . $identifier, ScopeInterface::SCOPE_STORE);
        if ($types) {
            $items = json_decode($types, true);
            foreach ($items as $item) {
                $values[] = [
                    'label' => $item['value'],
                    'value' => $item['key']
                ];
            }
        }
        return $values;
    }
}
