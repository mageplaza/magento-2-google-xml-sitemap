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

use Exception;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\Sitemap\Helper\Data as HelperConfig;
use Mageplaza\Sitemap\Model\Source\SortProduct;

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
        $this->collection          = $collection;
        $this->_categoryHelper     = $categoryHelper;
        $this->_categoryCollection = $categoryCollection;
        $this->categoryRepository  = $categoryRepository;
        $this->_helper             = $helper;
        $this->_stockFilter        = $stockFilter;
        $this->productVisibility   = $productVisibility;
        $this->productCollection   = $productCollection;
        $this->pageCollection      = $pageCollection;

        parent::__construct($context);
    }

    /**
     * Get product collection
     *
     * @return mixed
     */
    public function getProductCollection()
    {
        $limit      = $this->_helper->getProductLimit() ?: self::DEFAULT_PRODUCT_LIMIT;
        $collection = $this->productCollection->create()
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect('*');

        $sortProductBy  = $this->_helper->getHtmlSitemapConfig('product_sorting');
        $sortProductDir = $this->_helper->getHtmlSitemapConfig('product_sorting_dir');

        switch ($sortProductBy) {
            case SortProduct::PRODUCT_NAME:
                $collection->setOrder('name', $sortProductDir);
                break;
            case SortProduct::PRICE:
                $collection->setOrder('minimal_price', $sortProductDir);
                break;
            default:
                $collection->setOrder('entity_id', $sortProductDir);
                break;
        }

        if ($this->_helper->getHtmlSitemapConfig('out_of_stock_products')) {
            $this->_stockFilter->addInStockFilterToCollection($collection);
        }

        $collection->setPageSize($limit);

        return $collection;
    }

    /**
     * @return Collection|AbstractDb
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCategoryCollection()
    {
        $storeRootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();
        $storeRootCategory = $this->categoryRepository->get($storeRootCategoryId);
        $categoryCollection = $this->_categoryCollection->create()->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $storeRootCategory->getAllChildren(true)])
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('include_in_menu', 1)
            ->addFieldToFilter('entity_id', ['nin' => [$storeRootCategoryId]]);

        $excludeCategories = $this->_helper->getHtmlSitemapConfig('category_page');
        if (!empty($excludeCategories)) {
            $excludeCategories = array_map('trim', explode(
                "\n",
                $excludeCategories
            ));

            $allExcludeIds = '';
            foreach ($excludeCategories as $excludeCategory) {
                if (!empty($excludeCategory)) {
                    try {
                        $testRegex = preg_match($excludeCategory, '');
                        if ($testRegex) {
                            $allExcludeIds .= '-' . $this->filterCategoryWithRegex($excludeCategory);
                        } else {
                            $excludePath = $this->getExcludePath($excludeCategory);
                            $allExcludeIds .= '-' . $this->filterCategoryWithPath($excludePath, $categoryCollection);
                        }
                    } catch (Exception $e) {
                        $excludePath = $this->getExcludePath($excludeCategory);
                        $allExcludeIds .= '-' . $this->filterCategoryWithPath($excludePath, $categoryCollection);
                    }
                }
            }

            $excludeIds = explode('-', $allExcludeIds);
            $categoryCollection->addFieldToFilter('entity_id', ['nin' => $excludeIds]);
        }

        return $this->_categoryCollection->create()->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $categoryCollection->getAllIds()])->setOrder('path');
    }

    /**
     * @param $path
     * @param $categoryCollection
     *
     * @return string
     */
    protected function filterCategoryWithPath($path, $categoryCollection)
    {
        $excludeIds = [];
        foreach ($categoryCollection as $category) {
            if ($this->isExcludeCategory($category, $path)) {
                $excludeIds[] = $category->getData('entity_id');
            }
        }

        return implode('-', $excludeIds);
    }

    /**
     * @param $category
     * @param $path
     *
     * @return bool
     */
    public function isExcludeCategory($category, $path)
    {
        $filterPath = explode('/', $path);
        $categoryPath = $category->getUrlPath();
        $categoryPath = explode('/', $categoryPath);

        foreach ($filterPath as $pathInfo) {
            if (!in_array($pathInfo, $categoryPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $regex
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function filterCategoryWithRegex($regex)
    {
        $excludeCategoriesIds = [];
        $categoryCollection = $this->_categoryCollection->create()->addAttributeToSelect('*')
            ->setStoreId($this->_storeManager->getStore()->getId());
        foreach ($categoryCollection as $category) {
            if (!preg_match($regex, $category->getUrlPath())) {
                $excludeCategoriesIds[] = $category->getId();
            }
        }

        return implode('-', $excludeCategoriesIds);
    }

    /**
     * @param $excludeCategory
     *
     * @return string
     */
    protected function getExcludePath($excludeCategory)
    {
        if ($excludeCategory[0] == '/') {
            $excludeCategory = substr($excludeCategory, 1);
        }
        if ($excludeCategory[-1] == '/') {
            $excludeCategory = substr($excludeCategory, 0, -1);
        }

        return $excludeCategory;
    }

    /**
     * @param int $categoryId
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
     *
     * @return PageCollection
     * @throws NoSuchEntityException
     */
    public function getPageCollection()
    {
        $excludePages   = $this->_helper->getExcludePageListing();
        $pageCollection = $this->pageCollection->addFieldToFilter('is_active', Page::STATUS_ENABLED)
            ->addStoreFilter($this->_storeManager->getStore()->getId());

        if ($this->_helper->isEnableExcludePage() && !empty($excludePages)) {
            $pageCollection->addFieldToFilter('identifier', [
                'nin' => $this->getExcludedPages()
            ]);
        }

        return $pageCollection;
    }

    /**
     * Get excluded pages
     *
     * @return array
     */
    public function getExcludedPages()
    {
        return explode(',', $this->_helper->getExcludePageListing());
    }

    /**
     * Get addition link collection
     *
     * @return mixed
     */
    public function getAdditionLinksCollection()
    {
        $additionLinks = $this->_helper->getAdditionalLinks();
        $allLink       = explode("\n", $additionLinks);

        $result = [];
        foreach ($allLink as $link) {
            if (count($component = explode(',', $link)) > 1) {
                $result[$component[0]] = $component[1];
            }
        }

        return $result;
    }

    /**
     * @param $link
     * @param $title
     * @param $level
     *
     * @return string
     */
    public function renderLinkElement($link, $title, $level = null)
    {
        return '<li><a class="level-' . $level . '" href="' . $link . '">' . __($title) . '</a></li>';
    }

    // phpcs:disable Generic.Metrics.NestingLevel
    /**
     * @param string $section
     * @param bool $config
     * @param string $title
     * @param Object $collection
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
                            $category = $this->categoryRepository->get($item->getId());
                            if (!$category->getData('mp_exclude_sitemap')) {
                                $html .= $this->renderLinkElement(
                                    $this->getCategoryUrl($item->getId()),
                                    $item->getName(),
                                    $item->getLevel()
                                );
                            }
                            break;
                        case 'page':
                            if (in_array($item->getIdentifier(), $this->getExcludedPages())
                                || $item->getData('mp_exclude_sitemap')) {
                                continue 2;
                            }
                            $html .= $this->renderLinkElement($this->getUrl($item->getIdentifier()), $item->getTitle());
                            break;
                        case 'product':
                            if ($item->getData('mp_exclude_sitemap')) {
                                continue 2;
                            }
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
     *
     * @return mixed
     */
    public function isEnableHtmlSitemap()
    {
        return $this->_helper->isEnableHtmlSiteMap();
    }

    /**
     * @return array|bool|mixed
     */
    public function getCategoryDisplayType()
    {
        return $this->_helper->getHtmlSitemapConfig('display_type');
    }
}
