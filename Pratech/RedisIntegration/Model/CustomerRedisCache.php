<?php

namespace Pratech\RedisIntegration\Model;

use Predis\Client;

class CustomerRedisCache
{
    /**
     * Customer Purchased Products Key Identifier
     */
    public const CUSTOMER_PURCHASED_PRODUCTS_PREFIX = "customer:purchased-products:id";

    /**
     * Customer Store Credit Transactions Key Identifier
     */
    public const STORE_CREDIT_TRAN = "customer:store-credit-transactions:id";

    /**
     * Customer Personalized Widget Key Identifier
     */
    public const CUSTOMER_PERSONALIZED_WIDGET = "customer:widget";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * @param RedisConnection $redisConnection
     */
    public function __construct(
        RedisConnection $redisConnection
    ) {
        $this->redisConnection = $redisConnection->connect();
    }

    /**
     * Delete Customer Purchased Products Cache
     *
     * @param int $customerId
     * @return void
     */
    public function deleteCustomerPurchasedProducts(int $customerId): void
    {
        if ($this->redisConnection &&
            count($this->getKeys(self::CUSTOMER_PURCHASED_PRODUCTS_PREFIX . ":" . $customerId . "*"))) {
            $this->redisConnection->del(
                $this->getKeys(self::CUSTOMER_PURCHASED_PRODUCTS_PREFIX . ":" . $customerId . "*")
            );
        }
    }

    /**
     * Get Already Existing Redis Keys.
     *
     * @param string $pattern
     * @return array
     */
    private function getKeys(string $pattern): array
    {
        return $this->redisConnection->keys($pattern);
    }

    /**
     * Delete All Customers Purchased Products Cache
     *
     * @return void
     */
    public function deleteAllCustomerPurchasedProducts(): void
    {
        if ($this->redisConnection && count($this->getKeys(self::CUSTOMER_PURCHASED_PRODUCTS_PREFIX . "*"))) {
            $this->redisConnection->del($this->getKeys(self::CUSTOMER_PURCHASED_PRODUCTS_PREFIX . "*"));
        }
    }

    /**
     * Delete Customer Store Credit Transactions Cache
     *
     * @param int|null $customerId
     * @return void
     */
    public function deleteCustomerStoreCreditTransactions(?int $customerId): void
    {
        if ($customerId !== null) {
            if ($this->validateExistingKey(self::STORE_CREDIT_TRAN . ":" . $customerId)) {
                $this->redisConnection->del($this->getKeys(self::STORE_CREDIT_TRAN . ":" . $customerId));
            }
        }
    }

    /**
     * Validate Existing Cache Key.
     *
     * @param string $keyIdentifier
     * @return bool
     */
    private function validateExistingKey(string $keyIdentifier): bool
    {
        return $this->redisConnection && count($this->redisConnection->keys($keyIdentifier));
    }

    /**
     * Delete Customer Personalized Widget Cache
     *
     * @param int $customerId
     * @return void
     */
    public function deleteCustomerWidget(int $customerId): void
    {
        if ($this->redisConnection) {
            $this->redisConnection->select(1);
            if ($this->validateExistingKey(self::CUSTOMER_PERSONALIZED_WIDGET . ":" . $customerId)) {
                $this->redisConnection
                    ->del($this->getKeys(self::CUSTOMER_PERSONALIZED_WIDGET . ":" . $customerId));
            }
        }
    }

    /**
     * Delete All Customer Personalized Widget Cache
     *
     * @return void
     */
    public function deleteAllCustomerWidget(): void
    {
        if ($this->redisConnection) {
            $this->redisConnection->select(1);
            if ($this->validateExistingKey(self::CUSTOMER_PERSONALIZED_WIDGET . "*")) {
                $this->redisConnection->del($this->getKeys(self::CUSTOMER_PERSONALIZED_WIDGET . "*"));
            }
        }
    }
}
