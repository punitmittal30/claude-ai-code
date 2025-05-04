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
namespace Pratech\Return\Api\Data;

/**
 * Interface PaymentDetailsInterface
 */
interface PaymentDetailsInterface
{
    const UPI_ID = 'upi_id';
    const BANK_DETAILS = 'bank_account_details';

    /**
     * Get UPI ID
     *
     * @return string|null
     */
    public function getUpiId(): ?string;

    /**
     * Set UPI ID
     *
     * @param  string $upiId
     * @return $this
     */
    public function setUpiId(string $upiId): self;

    /**
     * Get Bank Account Details
     *
     * @return \Pratech\Return\Api\Data\BankAccountDetailsInterface|null
     */
    public function getBankAccountDetails(): ?BankAccountDetailsInterface;

    /**
     * Set Bank Account Details
     *
     * @param  \Pratech\Return\Api\Data\BankAccountDetailsInterface $bankAccountDetails
     * @return $this
     */
    public function setBankAccountDetails(BankAccountDetailsInterface $bankAccountDetails): self;
}
