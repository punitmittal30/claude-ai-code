<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pratech\Promotion\Model\Spi\PromoCodeResourceInterface;

class PromoCode extends AbstractDb implements PromoCodeResourceInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'code_id';

    /**
     * Check if code exists
     *
     * @param string $code
     * @return bool
     * @throws LocalizedException
     */
    public function exists(string $code): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getMainTable(),
            'promo_code'
        )->where(
            'promo_code = :promo_code'
        );

        if ($connection->fetchOne($select, ['promo_code' => $code]) === false) {
            return false;
        }
        return true;
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_promotion_code', 'code_id');
    }
}
