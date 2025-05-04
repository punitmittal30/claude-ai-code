<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Observer;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Ui\DataProvider\Product\Form\Modifier\FaqContent;

class SaveFaqContent implements ObserverInterface
{
    /**
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected RequestInterface      $request,
        protected SerializerInterface   $serializer
    ) {
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return this
     */
    public function execute(Observer $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getDataObject();
        $wholeRequest = $this->request->getPost();
        try {
            $post = $wholeRequest['product'];

            if (empty($post)) {
                $post = !empty($wholeRequest['variables']['product']) ? $wholeRequest['variables']['product'] : [];
            }
            $highlights = isset(
                $post[FaqContent::PRODUCT_ATTRIBUTE_CODE]
            ) ? $post[FaqContent::PRODUCT_ATTRIBUTE_CODE] : '';

            $product->setFaqContent($highlights);
            $requiredParams = ['question', 'answer'];
            if (is_array($highlights)) {
                $highlights = $this->removeEmptyArray($highlights, $requiredParams);
                $product->setFaqContent($this->serializer->serialize($highlights));
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Function to remove empty array from the multi dimensional array
     *
     * @param array $attractionData
     * @param array $requiredParams
     * @return array
     */
    private function removeEmptyArray($attractionData, $requiredParams)
    {
        $requiredParams = array_combine($requiredParams, $requiredParams);
        $reqCount = count($requiredParams);

        foreach ($attractionData as $key => $values) {
            $values = array_filter($values);
            $intersectCount = count(array_intersect_key($values, $requiredParams));
            if ($reqCount !== $intersectCount) {
                unset($attractionData[$key]);
            }
        }
        return $attractionData;
    }
}
