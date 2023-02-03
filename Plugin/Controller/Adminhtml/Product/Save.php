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

namespace Mageplaza\Sitemap\Plugin\Controller\Adminhtml\Product;

use Magento\Catalog\Controller\Adminhtml\Product\Save as SaveProduct;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use \Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Sitemap\Helper\Data;

class Save
{
    const YES = 1;
    const NO  = 0;

    /**
     * @var Helper
     */
    protected $initializationHelper;

    /**
     * @var Builder
     */
    protected $productBuilder;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductFactory
     */
    protected $_product;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * Save constructor.
     *
     * @param Data $helper
     * @param Helper $initializationHelper
     * @param Builder $productBuilder
     * @param StoreManagerInterface $_storeManager
     * @param ProductRepository $_productRepository
     * @param ProductFactory $_product
     */
    public function __construct(
        Data $helper,
        Helper $initializationHelper,
        Builder $productBuilder,
        StoreManagerInterface $_storeManager,
        ProductRepository $_productRepository,
        ProductFactory $_product,
    ) {
        $this->_helper              = $helper;
        $this->initializationHelper = $initializationHelper;
        $this->productBuilder       = $productBuilder;
        $this->_storeManager        = $_storeManager;
        $this->_product             = $_product;
        $this->_productRepository   = $_productRepository;
    }

    public function afterExecute(SaveProduct $subject, $result) {
        $productPostData  = $subject->getRequest()->getPostValue();
        $currentStoreId   = $productPostData['product']['current_store_id'];
        $productId        = $subject->getRequest()->getParam('id');
        $productAll       = $this->_product->create()->setStoreId(0)->load($productId);
        $productCurrent   = $this->initializationHelper->initialize($this->productBuilder->build($subject->getRequest()));

        /**
         * Check "Use Default Value" checkboxes values
         */
        if ($this->_helper->isEnabled($currentStoreId)
            && isset($productPostData['use_default'])
            && !empty($productPostData['use_default']))
        {
            foreach ($productPostData['use_default'] as $attributeCode => $attributeValue) {
                if ($attributeCode == 'mp_exclude_sitemap' && $attributeValue == self::YES) {
                    $productCurrent->setData('mp_sitemap_active_config', $productAll->getData('mp_sitemap_active_config'));
                } else if ($attributeCode == 'mp_exclude_sitemap' && $attributeValue == 0) {
                    $productCurrent->setData('mp_sitemap_active_config', self::NO);
                }
            }

            $productCurrent->save();
        }

        return $result;
    }
}
