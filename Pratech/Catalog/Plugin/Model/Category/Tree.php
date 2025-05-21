<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Plugin\Model\Category;

use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Framework\Data\Tree\Node;

/**
 * Override Tree Class in order to alter data returned by getTree method.
 */
class Tree extends \Magento\Catalog\Model\Category\Tree
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
     * @param \Magento\Catalog\Api\Data\CategoryTreeInterfaceFactory $treeFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param TreeFactory|null $treeResourceFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\Tree       $categoryTree,
        \Magento\Store\Model\StoreManagerInterface               $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection,
        \Magento\Catalog\Api\Data\CategoryTreeInterfaceFactory   $treeFactory,
        \Magento\Catalog\Model\CategoryFactory                   $categoryFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface         $categoryRepository,
        TreeFactory                                              $treeResourceFactory = null
    ) {
        parent::__construct($categoryTree, $storeManager, $categoryCollection, $treeFactory, $treeResourceFactory);
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get tree by node.
     *
     * @param Node $node
     * @param int $depth
     * @param int $currentLevel
     * @return \Pratech\Catalog\Api\Data\CategoryTreeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTree($node, $depth = null, $currentLevel = 0)
    {
        /** @var \Pratech\Catalog\Api\Data\CategoryTreeInterface[] $children */
        $children = $this->getChildren($node, $depth, $currentLevel);

        $category = $this->categoryRepository->get($node->getId());

        /** @var \Pratech\Catalog\Api\Data\CategoryTreeInterface $tree */
        $tree = $this->treeFactory->create();

        $tree->setId($node->getId())
            ->setParentId($node->getParentId())
            ->setName($node->getName())
            ->setImage($category->getImage())
            ->setCategoryThumbnail($category->getCategoryThumbnail())
            ->setCategoryIcon($category->getCategoryIcon())
            ->setUrlKey($category->getUrlKey())
            ->setDescription($category->getDescription())
            ->setPosition($node->getPosition())
            ->setLevel($node->getLevel())
            ->setIsActive($node->getIsActive())
            ->setPageType($category->getPageType())
            ->setMetaTitle($category->getMetaTitle())
            ->setMetaDescription($category->getMetaDescription())
            ->setMetaKeywords($category->getMetaKeywords())
            ->setProductCount($node->getProductCount())
            ->setChildrenData($children);

        return $tree;
    }

    /**
     * Get node children.
     *
     * @param Node $node
     * @param int $depth
     * @param int $currentLevel
     * @return \Pratech\Catalog\Api\Data\CategoryTreeInterface[]|[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getChildren($node, $depth, $currentLevel)
    {
        if ($node->hasChildren()) {
            $children = [];
            foreach ($node->getChildren() as $child) {
                $category = $this->categoryRepository->get($child->getId());
                if ($depth !== null && $depth <= $currentLevel) {
                    break;
                }
                if ($category->getIncludeInMenu()) {
                    $children[] = $this->getTree($child, $depth, $currentLevel + 1);
                }
            }
            return $children;
        }
        return [];
    }
}
