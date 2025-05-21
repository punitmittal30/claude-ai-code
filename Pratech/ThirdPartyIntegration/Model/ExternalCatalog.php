<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\ThirdPartyIntegration\Api\ExternalCatalogInterface;
use Pratech\ThirdPartyIntegration\Helper\ExternalCatalog as ExternalCatalogHelper;

class ExternalCatalog implements ExternalCatalogInterface
{
    /**
     * Constant for External API RESOURCE
     */
    public const EXTERNAL_API_RESOURCE = 'external';

    /**
     * External Catalog Constructor
     *
     * @param ExternalCatalogHelper $externalCatalogHelper
     * @param Response $response
     */
    public function __construct(
        private ExternalCatalogHelper $externalCatalogHelper,
        private Response              $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProductList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        return $this->externalCatalogHelper->getProductList($searchCriteria);
    }
}
