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

use Exception;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
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
        Item $stockItem,
        DateTime $modelDate,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        StdlibDateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helperConfig = $helperConfig;
        $this->_coreProductFactory = $coreProductFactory;
        $this->_corePageFactory = $corePageFactory;
        $this->_coreCategoryFactory = $coreCategoryFactory;
        $this->stockItem = $stockItem;

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
        $helper = $this->_sitemapData;
        $storeId = $this->getStoreId();
        $this->_sitemapItems = null;
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
     * @return $this
     * @throws Exception
     * @throws LocalizedException
     */
    public function generateXml()
    {
        $this->_initSitemapItems();
        /** @var $sitemapItem DataObject */
        foreach ($this->_sitemapItems as $item) {
            $changefreq = $item->getChangefreq();
            $priority = $item->getPriority();
            $urlType = $item->getUrlType();
            foreach ($item->getCollection() as $itemChild) {
                $xml = $this->getSitemapRow(
                    $itemChild->getUrl(),
                    $urlType,
                    $itemChild->getUpdatedAt(),
                    $changefreq,
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
            $path = rtrim($this->getSitemapPath(), '/') . '/'
                    . $this->_getCurrentSitemapFilename($this->_sitemapIncrement);
            $destination = rtrim($this->getSitemapPath(), '/') . '/' . $this->getSitemapFilename();

            $this->_directory->renameFile($path, $destination);
        } else {
            // Otherwise create index file with list of generated sitemaps
            $this->_createSitemapIndex();
        }

        // Push sitemap to robots.txt
        if ($this->_isEnabledSubmissionRobots()) {
            $this->_addSitemapToRobotsTxt($this->getSitemapFilename());
        }

        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Get site map row
     *
     * @param $url
     * @param $urlType
     * @param null $lastmod
     * @param null $changefreq
     * @param null $priority
     * @param null $images
     *
     * @return string
     */
    protected function getSitemapRow(
        $url,
        $urlType,
        $lastmod = null,
        $changefreq = null,
        $priority = null,
        $images = null
    ) {
        if ($urlType == self::URL) {
            $url = $this->_getUrl($url);
        } else {
            $url = $this->convertUrl($url);
        }
        $row = '<loc>' . htmlspecialchars($url) . '</loc>';
        if ($lastmod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastmod) . '</lastmod>';
        }
        if ($changefreq) {
            $row .= '<changefreq>' . $changefreq . '</changefreq>';
        }
        if ($priority) {
            $row .= sprintf('<priority>%.1f</priority>', $priority);
        }
        if ($images) {
            // Add Images to sitemap
            foreach ($images->getCollection() as $image) {
                $row .= '<image:image>';
                $row .= '<image:loc>' . htmlspecialchars($this->_getMediaUrl($image->getUrl())) . '</image:loc>';
                $row .= '<image:title>' . htmlspecialchars($images->getTitle()) . '</image:title>';
                if ($image->getCaption()) {
                    $row .= '<image:caption>' . htmlspecialchars($image->getCaption()) . '</image:caption>';
                }
                $row .= '</image:image>';
            }
            // Add PageMap image for Google web search
            $row .= '<PageMap xmlns="http://www.google.com/schemas/sitemap-pagemap/1.0"><DataObject type="thumbnail">';
            $row .= '<Attribute name="name" value="' . htmlspecialchars($images->getTitle()) . '"/>';
            $row .= '<Attribute name="src" value="' . htmlspecialchars($this->_getMediaUrl($images->getThumbnail()))
                    . '"/>';
            $row .= '</DataObject></PageMap>';
        }

        return '<url>' . $row . '</url>';
    }

    /**
     * Get link collection added by config Additional Links
     *
     * @param $storeId
     *
     * @return array
     */
    public function getLinkCollectionAdded($storeId)
    {
        $id = 1;
        $collection = [];
        foreach ($this->helperConfig->getXmlAdditionalLinks($storeId) as $item) {
            if ($item !== null) {
                $obj = ObjectManager::getInstance()->create(\Magento\Framework\DataObject::class);
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
     * @param $storeId
     *
     * @return array
     */
    public function _getCategoryCollection($storeId)
    {
        $collection = [];

        foreach ($this->_categoryFactory->create()->getCollection($storeId) as $item) {
            if ($this->_coreCategoryFactory->create()->load($item->getId())->getData('mp_exclude_sitemap') == 1) {
                continue;
            }
            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Get page collection
     *
     * @param $storeId
     *
     * @return array
     */
    public function _getPageCollection($storeId)
    {
        $collection = [];
        foreach ($this->_cmsFactory->create()->getCollection($storeId) as $item) {
            if ($this->_corePageFactory->create()->load($item->getId())->getData('mp_exclude_sitemap') == 1
                || $this->optimizeHomepage($storeId, $item)
            ) {
                continue;
            }
            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Get product Collection
     *
     * @param $storeId
     *
     * @return array
     */
    public function _getProductCollection($storeId)
    {
        $collection = [];
        foreach ($this->_productFactory->create()->getCollection($storeId) as $item) {
            if ($this->_coreProductFactory->create()->load($item->getId())->getData('mp_exclude_sitemap') == 1) {
                continue;
            }
            if ($this->stockItem->load($item->getId(), 'product_id')->getIsInStock() == 0) {
                continue;
            }
            $collection[] = $item;
        }

        return $collection;
    }

    /**
     * Convert Url
     *
     * @param $url
     *
     * @return string
     */
    public function convertUrl($url)
    {
        if (preg_match(self::PATTERN, $url)) {
            return $url;
        }

        return 'http://' . $url;
    }

    /**
     * Remove the link of the CMS page using for homepage.
     *
     * @param $storeId
     * @param $page
     *
     * @return bool
     */
    public function optimizeHomepage($storeId, $page)
    {
        return $this->helperConfig->isEnableHomepageOptimization($storeId) == 1
               && $this->helperConfig->getConfigValue(self::HOMEPAGE_PATH, $storeId) == $page->getUrl();
    }
}
