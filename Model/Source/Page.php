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

use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

/**
 * Class Page
 * @package Mageplaza\Sitemap\Model\Source
 */
class Page
{
    /**
     * @var CollectionFactory
     */
    protected $_pageCollectionFactory;

    /**
     * Page constructor.
     *
     * @param CollectionFactory $pageCollectionFactory
     */
    public function __construct(CollectionFactory $pageCollectionFactory)
    {
        $this->_pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Get list cms pages
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        /** @var Collection $collection */
        $collection = $this->_pageCollectionFactory->create();
        foreach ($collection as $item) {
            $options[] = ['value' => $item->getIdentifier(), 'label' => $item->getTitle()];
        }

        return $options;
    }
}
