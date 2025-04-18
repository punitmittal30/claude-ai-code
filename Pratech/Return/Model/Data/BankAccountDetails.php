<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\Data;

use Magento\Framework\DataObject;
use Pratech\Return\Api\Data\BankAccountDetailsInterface;

class BankAccountDetails extends DataObject implements BankAccountDetailsInterface
{
    /**
     * @inheritDoc
     */
    public function getAccountNumber(): ?string
    {
        return $this->getData(self::ACCOUNT_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setAccountNumber(string $accountNumber): self
    {
        return $this->setData(self::ACCOUNT_NUMBER, $accountNumber);
    }

    /**
     * @inheritDoc
     */
    public function getIfscCode(): ?string
    {
        return $this->getData(self::IFSC_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setIfscCode(string $ifscCode): self
    {
        return $this->setData(self::IFSC_CODE, $ifscCode);
    }

    /**
     * @inheritDoc
     */
    public function getAccountHolderName(): ?string
    {
        return $this->getData(self::ACCOUNT_HOLDER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setAccountHolderName(string $accountHolderName): self
    {
        return $this->setData(self::ACCOUNT_HOLDER_NAME, $accountHolderName);
    }
}
