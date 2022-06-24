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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Sitemap\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SortProduct
 * @package Mageplaza\Sitemap\Model\Config\Source
 */
class SortProduct implements ArrayInterface
{
    const PRODUCT_IDS  = 'product_ids';
    const PRODUCT_NAME = 'product_name';
    const PRICE        = 'price';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::PRODUCT_IDS  => __('Product IDs'),
            self::PRODUCT_NAME => __('Product Name'),
            self::PRICE        => __('Price')
        ];
    }
}
