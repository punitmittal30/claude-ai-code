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
use Pratech\Return\Api\Data\PaymentDetailsInterface;

class PaymentDetails extends DataObject implements PaymentDetailsInterface
{
    /**
     * @inheritDoc
     */
    public function getUpiId(): ?string
    {
        return $this->getData(self::UPI_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUpiId(string $upiId): self
    {
        return $this->setData(self::UPI_ID, $upiId);
    }

    /**
     * @inheritDoc
     */
    public function getBankAccountDetails(): ?BankAccountDetailsInterface
    {
        return $this->getData(self::BANK_DETAILS);
    }

    /**
     * @inheritDoc
     */
    public function setBankAccountDetails(BankAccountDetailsInterface $bankAccountDetails): self
    {
        return $this->setData(self::BANK_DETAILS, $bankAccountDetails);
    }
}
