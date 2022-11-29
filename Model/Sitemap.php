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

namespace Mageplaza\Sitemap\Model;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sitemap\Helper\Data;
use Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory as CatalogCategoryFactory;
use Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory as CatalogProductFactory;
use Magento\Sitemap\Model\ResourceModel\Cms\PageFactory as CmsPageFactory;
use Magento\Sitemap\Model\Sitemap as CoreSitemap;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Sitemap\Helper\Data as HelperConfig;
use Zend_Db_Statement_Exception;

/**
 * Class Sitemap
 * @package Mageplaza\Sitemap\Model
 */
class Sitemap extends CoreSitemap
{
    const PATTERN       = '/http:\\\\/';
    const URL_BASIC     = 0;
    const URL           = 1;
    const HOMEPAGE_PATH = 'web/default/cms_home_page';

    /**
     * @var CategoryFactory
     */
    protected $_coreCategoryFactory;

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
     * @var ProductFactory
     */
    protected $_coreProductFactory;

    /**
     * @var PageFactory
     */
    protected $_corePageFactory;

    /**
     * @var HelperConfig
     */
    protected $helperConfig;

    /**
     * @var Item
     */
    protected $stockItem;

    /**
     * Sitemap constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Escaper $escaper
     * @param Data $sitemapData
     * @param Filesystem $filesystem
     * @param CatalogCategoryFactory $categoryFactory
     * @param CatalogProductFactory $productFactory
     * @param CmsPageFactory $cmsFactory
     * @param HelperConfig $helperConfig
     * @param PageFactory $corePageFactory
     * @param ProductFactory $coreProductFactory
     * @param CategoryFactory $coreCategoryFactory
     * @param Collection $collection
     * @param CollectionFactory $categoryCollection
     * @param CategoryRepository $categoryRepository
     * @param Item $stockItem
     * @param DateTime $modelDate
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param StdlibDateTime $dateTime
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Escaper $escaper,
        Data $sitemapData,
        Filesystem $filesystem,
        CatalogCategoryFactory $categoryFactory,
        CatalogProductFactory $productFactory,
        CmsPageFactory $cmsFactory,
        HelperConfig $helperConfig,
        PageFactory $corePageFactory,
        ProductFactory $coreProductFactory,
        CategoryFactory $coreCategoryFactory,
        Collection $collection,
        CollectionFactory $categoryCollection,
        CategoryRepository $categoryRepository,
        Item $stockItem,
        DateTime $modelDate,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        StdlibDateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->collection           = $collection;
        $this->_categoryCollection  = $categoryCollection;
        $this->categoryRepository   = $categoryRepository;
        $this->helperConfig         = $helperConfig;
        $this->_coreProductFactory  = $coreProductFactory;
        $this->_corePageFactory     = $corePageFactory;
        $this->_coreCategoryFactory = $coreCategoryFactory;
        $this->stockItem            = $stockItem;

        parent::__construct(
            $context,
            $registry,
            $escaper,
            $sitemapData,
            $filesystem,
            $categoryFactory,
            $productFactory,
            $cmsFactory,
            $modelDate,
            $storeManager,
            $request,
            $dateTime,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Init site map items
     */
    public function _initSitemapItems()
    {
        parent::_initSitemapItems();
        $helper                = $this->_sitemapData;
        $storeId               = $this->getStoreId();
        $this->_sitemapItems   = null;
        $this->_sitemapItems[] = new DataObject(
            [
                'changefreq' => $helper->getCategoryChangefreq($storeId),
                'priority'   => $helper->getCategoryPriority($storeId),
                'collection' => $this->_getCategoryCollection($storeId),
                'url_type'   => self::URL,
            ]
        );

        $this->_sitemapItems[] = new DataObject(
            [
                'changefreq' => $helper->getProductChangefreq($storeId),
                'priority'   => $helper->getProductPriority($storeId),
                'collection' => $this->_getProductCollection($storeId),
                'url_type'   => self::URL,
            ]
        );

        $this->_sitemapItems[] = new DataObject(
            [
                'changefreq' => $helper->getPageChangefreq($storeId),
                'priority'   => $helper->getPagePriority($storeId),
                'collection' => $this->_getPageCollection($storeId),
                'url_type'   => self::URL,
            ]
        );

        if ($this->helperConfig->isEnableAdditionalLinks($storeId)) {
            $this->_sitemapItems[] = new DataObject(
                [
                    'changefreq' => $this->helperConfig->getFrequency($storeId),
                    'priority'   => $this->helperConfig->getPriority($storeId),
                    'collection' => $this->getLinkCollectionAdded($storeId),
                    'url_type'   => self::URL_BASIC,
                ]
            );
        }
    }

    /**
     * @return $this|CoreSitemap
     * @throws LocalizedException
     * @throws FileSystemException
     * @throws ValidatorException
     */
    public function generateXml()
    {
        $this->_initSitemapItems();
        /** @var $sitemapItem DataObject */
        foreach ($this->_sitemapItems as $item) {
            $changeFreq = $item->getChangefreq();
            $priority   = $item->getPriority();
            $urlType    = $item->getUrlType();
            foreach ($item->getCollection() as $itemChild) {
                $xml = $this->getSitemapRow(
                    $itemChild->getUrl(),
                    $urlType,
                    $itemChild->getUpdatedAt(),
                    $changeFreq,
                    $priority,
                    $itemChild->getImages()
                );
                if ($this->_isSplitRequired($xml) && $this->_sitemapIncrement > 0) {
                    $this->_finalizeSitemap();
                }
                if (!$this->_fileSize) {
                    $this->_createSitemap();
                }
                $this->_writeSitemapRow($xml);
                // Increase counters
                $this->_lineCount++;
                $this->_fileSize += strlen($xml);
            }
        }
        $this->_finalizeSitemap();

        if ($this->_sitemapIncrement == 1) {
            // In case when only one increment file was created use it as default sitemap
            $path        = rtrim($this->getSitemapPath(), '/') . '/'
                . $this->_getCurrentSitemapFilename($this->_sitemapIncrement);
            $destination = rtrim($this->getSitemapPath(), '/') . '/' . $this->getSitemapFilename();

            $this->_directory->renameFile($path, $destination);
        } else {
            // Otherwise create index file with list of generated sitemaps
            $this->_createSitemapIndex();
        }

        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Get site map row
     *
     * @param string $url
     * @param int $urlType
     * @param null $lastMod
     * @param null $changeFreq
     * @param null $priority
     * @param null $images
     *
     * @return string
     */
    protected function getSitemapRow(
        $url,
        $urlType,
        $lastMod = null,
        $changeFreq = null,
        $priority = null,
        $images = null
    ) {
        $url = $this->convertUrlCollection($urlType, $url);
        $row = '<loc>' . htmlspecialchars($url) . '</loc>';
        if ($lastMod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastMod) . '</lastmod>';
        }
        if ($changeFreq) {
            $row .= '<changefreq>' . $changeFreq . '</changefreq>';
        }
        if ($priority) {
            $row .= sprintf('<priority>%.1f</priority>', $priority);
        }
        if ($images) {
            // Add Images to sitemap
            foreach ($images->getCollection() as $image) {
                $row .= '<image:image>';
                $row .= '<image:loc>' . htmlspecialchars($image->getUrl()) . '</image:loc>';
                $row .= '<image:title>' . htmlspecialchars($images->getTitle()) . '</image:title>';
                if ($image->getCaption()) {
                    $row .= '<image:caption>' . htmlspecialchars($image->getCaption()) . '</image:caption>';
                }
                $row .= '</image:image>';
            }
            // Add PageMap image for Google web search
            $row .= '<PageMap xmlns="http://www.google.com/schemas/sitemap-pagemap/1.0"><DataObject type="thumbnail">';
            $row .= '<Attribute name="name" value="' . htmlspecialchars($images->getTitle()) . '"/>';
            $row .= '<Attribute name="src" value="' . htmlspecialchars($images->getThumbnail())
                . '"/>';
            $row .= '</DataObject></PageMap>';
        }

        return '<url>' . $row . '</url>';
    }

    /**
     * Get link collection added by config Additional Links
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getLinkCollectionAdded($storeId)
    {
        $id                = 1;
        $collection        = [];
        $excludeLinkConfig = $this->helperConfig->getXmlSitemapConfig('exclude_links');
        foreach ($this->helperConfig->getXmlAdditionalLinks($storeId) as $item) {
            if ($excludeLinkConfig && str_contains($excludeLinkConfig,$item)) {
                continue;
            }
            if ($item !== null) {
                $obj = ObjectManager::getInstance()->create(DataObject::class);
                $obj->setData('id', $id++);
                $obj->setData('url', $item);
                $obj->setData('updated_at', $this->getSitemapTime());

                $collection[] = $obj;
            }
        }

        return $collection;
    }

    /**
     * Get category collection
     *
     * @param int $storeId
     *
     * @return array
     */
    public function _getCategoryCollection($storeId)
    {
        $collection          = [];
        $storeRootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();
        $storeRootCategory   = $this->categoryRepository->get($storeRootCategoryId);
        $categoryCollection  = $this->_categoryCollection->create()->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $storeRootCategory->getAllChildren(true)])
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('include_in_menu', 1)
            ->addFieldToFilter('entity_id', ['nin' => [$storeRootCategoryId]]);
        $excludeCategoryIds  = $this->getExcludeCategoryIds($categoryCollection);
        foreach ($categoryCollection as $item) {
            $category = $this->_coreCategoryFactory->create()->load($item->getId());
            $baseUrl  = $this->convertUrlCollection(self::URL, $item->getUrl());
            if ($category->getId() && $category->getData('mp_sitemap_active_config') == null) {
                $category->save();
            }
            if ($category->getData('mp_sitemap_active_config') == 1) {
                $excludeLinkConfig = $this->helperConfig->getXmlSitemapConfig('exclude_links');
                if ($excludeLinkConfig && str_contains($excludeLinkConfig, $baseUrl)) {
                    continue;
                }
                if ($excludeCategoryIds && in_array($item->getId(), $excludeCategoryIds)) {
                    continue;
                }
            } else {
                if ($category->getData('mp_exclude_sitemap') == 1) {
                    continue;
                }
            }

            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Get page collection
     *
     * @param int $storeId
     *
     * @return array
     */
    public function _getPageCollection($storeId)
    {
        $collection        = [];
        $excludePageConfig = explode(',', $this->helperConfig->getXmlSitemapConfig('exclude_page_sitemap') ?? '');
        $excludeLinkConfig = $this->helperConfig->getXmlSitemapConfig('exclude_links');
        foreach ($this->_cmsFactory->create()->getCollection($storeId) as $item) {
            $pageData = $this->_corePageFactory->create()->load($item->getId());
            $baseUrl  = $this->convertUrlCollection(self::URL, $item->getUrl());
            if ($pageData->getId() && $pageData->getData('mp_sitemap_active_config') == null) {
                $pageData->save();
            }

            if ($pageData->getData('mp_sitemap_active_config') == 1
                && (in_array($item->getUrl(), $excludePageConfig)
                    || ($excludeLinkConfig && str_contains($excludeLinkConfig, $baseUrl)))
            ) {
                continue;
            } else {
                if ($pageData->getData('mp_exclude_sitemap') == 1
                    || $this->optimizeHomepage($storeId, $item)
                ) {
                    continue;
                }
            }
            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Get product Collection
     *
     * @param int $storeId
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function _getProductCollection($storeId)
    {
        $collection         = [];
        $ProductCollections = $this->_productFactory->create()->getCollection($storeId);
        $productTypeConfig  = explode(',', $this->helperConfig->getXmlSitemapConfig('exclude_product_type') ?? '');
        $urlsConfig         = $this->helperConfig->getXmlSitemapConfig('exclude_product_page');
        $excludeLinkConfig  = $this->helperConfig->getXmlSitemapConfig('exclude_links');
        foreach ($ProductCollections as $item) {
            $product = $this->_coreProductFactory->create()->load($item->getId());
            $baseUrl = $this->convertUrlCollection(self::URL, $item->getUrl());
            if ($product->getId() && $product->getData('mp_sitemap_active_config') == null) {
                $product->save();
            }

            if ($product->getData('mp_sitemap_active_config') == 1
                && (in_array($product->getTypeId(), $productTypeConfig)
                    || ($excludeLinkConfig && str_contains($excludeLinkConfig, $baseUrl))
                    || ($urlsConfig && str_contains($urlsConfig, $product->getUrlKey())))
            ) {
                continue;
            } else {
                if ($product->getData('mp_exclude_sitemap') == 1) {
                    continue;
                }
            }

            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Convert Url
     *
     * @param string $url
     *
     * @return string
     */
    public function convertUrl($url)
    {
        if (preg_match('@^http://@i', $url) || preg_match('@^https://@i', $url)) {
            return $url;
        }

        return 'http://' . $url;
    }

    /**
     * Remove the link of the CMS page using for homepage.
     *
     * @param int $storeId
     * @param Object $page
     *
     * @return bool
     */
    public function optimizeHomepage($storeId, $page)
    {
        return $this->helperConfig->isEnableHomepageOptimization($storeId) == 1
            && $this->helperConfig->getConfigValue(self::HOMEPAGE_PATH, $storeId) == $page->getUrl();
    }

    /**
     * @param $urlType
     * @param $url
     * @return string
     */
    public function convertUrlCollection($urlType, $url) {
        if ($urlType == self::URL) {
            $url = $this->_getUrl($url);
        } else {
            $url = $this->convertUrl($url);
        }

        return $url;
    }

    /**
     * @param $categoryCollection
     * @return false|string[]
     */
    public function getExcludeCategoryIds($categoryCollection) {
        $excludeCategories = $this->helperConfig->getXmlSitemapConfig('exclude_category_page');
        $excludeIds        = [];
        if (!empty($excludeCategories)) {
            $excludeCategories = array_map('trim', explode(
                "\n",
                $excludeCategories
                ?? ''));

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
                    } catch (\Exception $e) {
                        $excludePath = $this->getExcludePath($excludeCategory);
                        $allExcludeIds .= '-' . $this->filterCategoryWithPath($excludePath, $categoryCollection);
                    }
                }
            }

            $excludeIds = explode('-', $allExcludeIds ?? '');
        }

        return $excludeIds;
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
        $filterPath = explode('/', $path ?? '');
        $categoryPath = $category->getUrlPath();
        $categoryPath = explode('/', $categoryPath ?? '');

        foreach ($filterPath as $pathInfo) {
            if (!in_array($pathInfo, $categoryPath)) {
                return false;
            }
        }

        return true;
    }
}
