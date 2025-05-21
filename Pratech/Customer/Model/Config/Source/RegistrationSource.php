<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class RegistrationSource extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => '', 'label' => __('Please Select')],
                ['value' => 'web', 'label' => __('Web')],
                ['value' => 'app', 'label' => __('Mobile App')],
                ['value' => 'dpanda', 'label' => __('Dpanda')],
                ['value' => 'other', 'label' => __('Other')]
            ];
        }
        return $this->_options;
    }

    /**
     * Validate Registration Source.
     *
     * @param string|null $registrationSource
     * @return bool
     */
    public function validateRegistrationSource(?string $registrationSource): bool
    {
        foreach ($this->getAllOptions() as $option) {
            if ($registrationSource === $option['value'] && $option['value'] != "") {
                return true;
            }
        }
        return false;
    }
}
