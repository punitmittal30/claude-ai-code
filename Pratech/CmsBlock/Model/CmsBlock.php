<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model;

use Exception;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Model\Data\Response;
use Pratech\CmsBlock\Api\CmsBlockInterface;

/**
 * CMS Block Class to implement Cms Api
 */
class CmsBlock implements CmsBlockInterface
{
    public const CONTENT_API_RESOURCE = 'content';

    /**
     * CmsBlock Constructor
     *
     * @param StoreManagerInterface         $storeManager
     * @param GetBlockByIdentifierInterface $getBlockByIdentifier
     * @param FilterProvider                $filter
     * @param Response                      $response
     */
    public function __construct(
        private StoreManagerInterface         $storeManager,
        private GetBlockByIdentifierInterface $getBlockByIdentifier,
        private FilterProvider                $filter,
        private Response                      $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCmsBlockByIdentifier(string $identifier): array
    {
        $storeId = $this->storeManager->getWebsite(0)->getDefaultStore()->getId();
        $block = $this->getBlockByIdentifier->execute($identifier, $storeId);
        if (!$block->getId() || !$block->isActive()) {
            throw new NoSuchEntityException(__('CMS Block with identifier "%1" does not exist.', $identifier));
        }
        $blockData = [
            "id" => $block->getId(),
            "title" => $block->getTitle(),
            "content" => $block->getContent(),
            "identifier" => $block->getIdentifier(),
            "creation_time" => $block->getCreationTime(),
            "update_time" => $block->getUpdateTime()
        ];
        $blockData["content"] = $this->filterStaticBlockContent($blockData["content"]);
        return $blockData;
    }

    /**
     * Filter Static Block Content
     *
     * @param  string|null $blockContent
     * @return string
     * @throws Exception
     */
    public function filterStaticBlockContent(?string $blockContent): string
    {
        return $this->filter->getBlockFilter()->filter($blockContent);
    }
}
