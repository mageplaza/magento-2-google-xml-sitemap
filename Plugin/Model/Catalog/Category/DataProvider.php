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

namespace Mageplaza\Sitemap\Plugin\Model\Catalog\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Sitemap\Helper\Data;

/**
 * Class DataProvider
 * @package Mageplaza\Sitemap\Plugin\Model\Catalog\Category
 */
class DataProvider
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * DataProvider constructor.
     *
     * @param Data $helper
     * @param StoreManagerInterface $_storeManager
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $_storeManager
    ) {
        $this->_helper       = $helper;
        $this->_storeManager = $_storeManager;
    }

    /**
     * @param CategoryDataProvider $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetData(CategoryDataProvider $subject, $result)
    {
        $category = $subject->getCurrentCategory();
        if ($this->_helper->isEnabled()) {
            if ($this->_storeManager->getStore()->getStoreId() != 0) {
                $result[$category->getId()]['visible'] = false;
            } else {
                $result[$category->getId()]['visible'] = true;
            }
        }

        return $result;
    }
}
