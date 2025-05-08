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
 * Interface BankAccountDetailsInterface
 */
interface BankAccountDetailsInterface
{
    const ACCOUNT_NUMBER = 'account_number';
    const IFSC_CODE = 'ifsc_code';
    const ACCOUNT_HOLDER_NAME = 'account_holder_name';

    /**
     * Get Account Number
     *
     * @return string|null
     */
    public function getAccountNumber(): ?string;

    /**
     * Set Account Number
     *
     * @param  string $accountNumber
     * @return $this
     */
    public function setAccountNumber(string $accountNumber): self;

    /**
     * Get IFSC Code
     *
     * @return string|null
     */
    public function getIfscCode(): ?string;

    /**
     * Set IFSC Code
     *
     * @param  string $ifscCode
     * @return $this
     */
    public function setIfscCode(string $ifscCode): self;

    /**
     * Get Account Holder Name
     *
     * @return string|null
     */
    public function getAccountHolderName(): ?string;

    /**
     * Set Account Holder Name
     *
     * @param  string $accountHolderName
     * @return $this
     */
    public function setAccountHolderName(string $accountHolderName): self;
}
