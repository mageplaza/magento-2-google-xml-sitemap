<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Sitemap
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Sitemap\Model\Source;

use Magento\Catalog\Model\ProductTypeList;

/**
 * Class Type
 * @package Mageplaza\Sitemap\Model\Source
 */
class Type
{
    /**
     * @var ProductTypeList
     */
    protected $productTypeList;

    /**
     * Type constructor.
     *
     * @param ProductTypeList $productTypeList
     */
    public function __construct(ProductTypeList $productTypeList)
    {
        $this->productTypeList = $productTypeList;
    }

    /**
     * Get list product type
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = [
            'value' => '',
            'label' => __('-- Please select --')
        ];

        /** @var ProductTypeList $productTypes */
        $productTypes = $this->productTypeList->getProductTypes();
        foreach ($productTypes as $item) {
            $options[] = ['value' => $item->getName(), 'label' => $item->getLabel()];
        }

        return $options;
    }
}
