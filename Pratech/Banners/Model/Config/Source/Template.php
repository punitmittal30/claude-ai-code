<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Template Class to provide source data to ui component.
 */
class Template implements OptionSourceInterface
{

    /**
     * Template Constructor.
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
        $options = [];
        $templates = $this->scopeConfig->getValue('banners/types/template', ScopeInterface::SCOPE_STORE);
        if ($templates) {
            $items = json_decode($templates, true);
            foreach ($items as $item) {
                $options[] = [
                    'label' => $item['value'],
                    'value' => $item['key']
                ];
            }
        }
        return $options;
    }
}
