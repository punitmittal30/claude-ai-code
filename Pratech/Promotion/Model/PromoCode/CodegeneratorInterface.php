<?php

namespace Pratech\Promotion\Model\PromoCode;

/**
 * @api
 * @since 100.0.2
 */
interface CodegeneratorInterface
{
    /**
     * Retrieve generated code
     *
     * @return string
     */
    public function generateCode();

    /**
     * Retrieve delimiter
     *
     * @return string
     */
    public function getDelimiter();
}
