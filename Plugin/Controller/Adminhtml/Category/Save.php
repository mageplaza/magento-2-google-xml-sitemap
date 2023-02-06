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

namespace Mageplaza\Sitemap\Plugin\Controller\Adminhtml\Category;

use Magento\Catalog\Controller\Adminhtml\Category\Save as SaveCategory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\Sitemap\Helper\Data;

/**
 * Class Save
 * @package Mageplaza\Sitemap\Plugin\Controller\Adminhtml\Category
 */
class Save
{
    const YES = 1;
    const NO  = 0;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CategoryRepository
     */
    protected $_category;

    /**
     * Save constructor.
     *
     * @param Data $helper
     * @param StoreManagerInterface $_storeManager
     * @param CategoryRepository $_category
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $_storeManager,
        CategoryRepository $_category,
    ) {
        $this->_helper       = $helper;
        $this->_storeManager = $_storeManager;
        $this->_category     = $_category;
    }

    /**
     * After Execute
     *
     * @param SaveCategory $subject
     * @param $result
     * @return mixed
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function afterExecute(SaveCategory $subject, $result) {
        $categoryPostData = $subject->getRequest()->getPostValue();
        $categoryId       = (int) $categoryPostData['entity_id'];
        $categoryAll      = $this->_category->get($categoryId, 0);
        $categoryCurrent  = $this->_category->get($categoryId, $categoryPostData['store_id']);

        /**
         * Check "Use Default Value" checkboxes values
         */
        if ($this->_helper->isEnabled($categoryPostData['store_id'])
            && isset($categoryPostData['use_default'])
            && !empty($categoryPostData['use_default'])
            && $categoryPostData['store_id'] != 0
        ) {
            foreach ($categoryPostData['use_default'] as $attributeCode => $attributeValue) {
                if ($attributeCode == 'mp_exclude_sitemap' && $attributeValue == 1) {
                    $categoryCurrent->setData($attributeCode, null);
                    $categoryCurrent->setData('mp_sitemap_active_config', $categoryAll->getData('mp_sitemap_active_config'));
                } else if ($attributeCode == 'mp_exclude_sitemap' && $attributeValue == 0) {
                    $categoryCurrent->setData('mp_sitemap_active_config', self::NO);
                }
            }

            $this->_category->save($categoryCurrent);
        }

        return $result;
    }
}
