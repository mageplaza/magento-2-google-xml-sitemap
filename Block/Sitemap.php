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

namespace Mageplaza\Sitemap\Block;

use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\Sitemap\Helper\Data as HelperConfig;

/**
 * Class Sitemap
 * @package Mageplaza\Sitemap\Block
 */
class Sitemap extends Template
{
    const DEFAULT_PRODUCT_LIMIT = 100;

    /**
     * @var Category
     */
    protected $_categoryHelper;

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollection;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var HelperConfig
     */
    protected $_helper;

    /**
     * @var Stock
     */
    protected $_stockFilter;

    /**
     * @var ProductVisibility
     */
    protected $productVisibility;

    /**
     * @var ProductCollection
     */
    protected $productCollection;

    /**
     * @var PageCollection
     */
    protected $pageCollection;

    /**
     * Sitemap constructor.
     *
     * @param Context $context
     * @param Category $categoryHelper
     * @param Collection $collection
     * @param CollectionFactory $categoryCollection
     * @param CategoryRepository $categoryRepository
     * @param HelperConfig $helper
     * @param Stock $stockFilter
     * @param ProductVisibility $productVisibility
     * @param ProductCollection $productCollection
     * @param PageCollection $pageCollection
     */
    public function __construct(
        Context $context,
        Category $categoryHelper,
        Collection $collection,
        CollectionFactory $categoryCollection,
        CategoryRepository $categoryRepository,
        HelperConfig $helper,
        Stock $stockFilter,
        ProductVisibility $productVisibility,
        ProductCollection $productCollection,
        PageCollection $pageCollection
    ) {
        $this->collection = $collection;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryCollection = $categoryCollection;
        $this->categoryRepository = $categoryRepository;
        $this->_helper = $helper;
        $this->_stockFilter = $stockFilter;
        $this->productVisibility = $productVisibility;
        $this->productCollection = $productCollection;
        $this->pageCollection = $pageCollection;

        parent::__construct($context);
    }

    /**
     * Get product collection
     * @return mixed
     */
    public function getProductCollection()
    {
        $limit = $this->_helper->getProductLimit() ?: self::DEFAULT_PRODUCT_LIMIT;
        $collection = $this->productCollection
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->setPageSize($limit)
            ->addAttributeToSelect('*');
        $this->_stockFilter->addInStockFilterToCollection($collection);

        return $collection;
    }

    /**
     * Get category collection
     * @return \Magento\Framework\Data\Tree\Node\Collection
     */
    public function getCategoryCollection()
    {
        return $this->_categoryHelper->getStoreCategories(false, true);
    }

    /**
     * @param $categoryId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryUrl($categoryId)
    {
        return $this->_categoryHelper->getCategoryUrl($this->categoryRepository->get($categoryId));
    }

    /**
     * Get page collection
     * @return mixed
     */
    public function getPageCollection()
    {
        return $this->pageCollection->addFieldToFilter('is_active', Page::STATUS_ENABLED)
            ->addFieldToFilter('identifier', [
                'nin' => $this->getExcludedPages()
            ]);
    }

    /**
     * Get excluded pages
     * @return array
     */
    public function getExcludedPages()
    {
        if ($this->_helper->isEnableExcludePage()) {
            return explode(',', $this->_helper->getExcludePageListing());
        }

        return ['home', 'no-route'];
    }

    /**
     * Get addition link collection
     * @return mixed
     */
    public function getAdditionLinksCollection()
    {
        $additionLinks = $this->_helper->getAdditionalLinks();
        $allLink = explode("\n", $additionLinks);

        $result = [];
        foreach ($allLink as $link) {
            if (count($component = explode(',', $link)) > 1) {
                $result[$component[0]] = $component[1];
            }
        }

        return $result;
    }

    /**
     * Render link element
     *
     * @param $link
     * @param $title
     *
     * @return string
     */
    public function renderLinkElement($link, $title)
    {
        return '<li><a href="' . $link . '">' . __($title) . '</a></li>';
    }

    /**
     * @param $section
     * @param $config
     * @param $title
     * @param $collection
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderSection($section, $config, $title, $collection)
    {
        $html = '';
        if ($config) {
            $html .= '<div class="row">';
            $html .= '<h2>' . $title . '</h2>';
            if ($collection) {
                $html .= '<ul class="mp-sitemap-listing">';
                foreach ($collection as $key => $item) {
                    switch ($section) {
                        case 'category':
                            $html .= $this->renderLinkElement($this->getCategoryUrl($item->getId()), $item->getName());
                            break;
                        case 'page':
                            if (in_array($item->getIdentifier(), $this->getExcludedPages())) {
                                continue 2;
                            }
                            $html .= $this->renderLinkElement($this->getUrl($item->getIdentifier()), $item->getTitle());
                            break;
                        case 'product':
                            $html .= $this->renderLinkElement($this->getUrl($item->getProductUrl()), $item->getName());
                            break;
                        case 'link':
                            $html .= $this->renderLinkElement($key, $item);
                            break;
                    }
                }
                $html .= '</ul>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderHtmlSitemap()
    {
        $htmlSitemap = '';
        $htmlSitemap .= $this->renderSection(
            'category',
            $this->_helper->isEnableCategorySitemap(),
            'Categories',
            $this->getCategoryCollection()
        );
        $htmlSitemap .= $this->renderSection(
            'page',
            $this->_helper->isEnablePageSitemap(),
            'Pages',
            $this->getPageCollection()
        );
        $htmlSitemap .= $this->renderSection(
            'product',
            $this->_helper->isEnableProductSitemap(),
            'Products',
            $this->getProductCollection()
        );
        $htmlSitemap .= $this->renderSection(
            'link',
            $this->_helper->isEnableAddLinksSitemap(),
            'Additional links',
            $this->getAdditionLinksCollection()
        );

        return $htmlSitemap;
    }

    /**
     * Is enable html site map
     * @return mixed
     */
    public function isEnableHtmlSitemap()
    {
        return $this->_helper->isEnableHtmlSiteMap();
    }
}
