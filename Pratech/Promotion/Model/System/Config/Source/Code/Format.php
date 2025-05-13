<?php

namespace Pratech\Promotion\Model\System\Config\Source\Code;

use Pratech\Promotion\Helper\Code;

class Format implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Pratech\Promotion\Helper\Code
     */
    protected $_promoCodeHelper = null;

    /**
     * @param Code $promoCodeHelper
     */
    public function __construct(\Pratech\Promotion\Helper\Code $promoCodeHelper)
    {
        $this->_promoCodeHelper = $promoCodeHelper;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $formatsList = $this->_promoCodeHelper->getFormatsList();
        $result = [];
        foreach ($formatsList as $formatId => $formatTitle) {
            $result[] = ['value' => $formatId, 'label' => $formatTitle];
        }

        return $result;
    }
}
