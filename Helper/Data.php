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

namespace Mageplaza\Sitemap\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Seo\Helper\Data as AbstractHelper;

/**
 * Class Config
 * @package Mageplaza\Sitemap\Helper
 */
class Data extends AbstractHelper
{
    const HTML_SITEMAP_CONFIGUARATION = 'html_sitemap/';
    const XML_SITEMAP_CONFIGUARATION  = 'xml_sitemap/';

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        StockItemRepository $stockItemRepository,
        TransportBuilder $transportBuilder,
        UrlInterface $backendUrl
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->backendUrl       = $backendUrl;

        parent::__construct($context, $objectManager, $storeManager, $stockItemRepository);
    }

    /************************ HTML Sitemap Configuration *************************
     * Is enable html site map
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnableHtmlSiteMap($storeId = null)
    {
        return $this->getHtmlSitemapConfig('enable', $storeId);
    }

    /**
     * @param $code
     * @param null $storeId
     *
     * @return array|bool|mixed
     */
    public function getHtmlSitemapConfig($code, $storeId = null)
    {
        return $this->getConfigValue(
            self::CONFIG_MODULE_PATH . '/' . self::HTML_SITEMAP_CONFIGUARATION . $code,
            $storeId
        );
    }

    /**
     * Is enable Category site map
     * @return mixed
     */
    public function isEnableCategorySitemap()
    {
        return $this->getHtmlSitemapConfig('category');
    }

    /**
     * Is enable page site map
     * @return mixed
     */
    public function isEnablePageSitemap()
    {
        return $this->getHtmlSitemapConfig('page');
    }

    /**
     * Is enable product site map
     * @return mixed
     */
    public function isEnableProductSitemap()
    {
        return $this->getHtmlSitemapConfig('product');
    }

    /**
     * Is enable add links site map
     * @return mixed
     */
    public function isEnableAddLinksSitemap()
    {
        return $this->getHtmlSitemapConfig('enable_add_links');
    }

    /**
     * Get additional links
     * @return mixed
     */
    public function getAdditionalLinks()
    {
        return $this->getHtmlSitemapConfig('additional_links');
    }

    /**
     * Is enable excludePage
     * @return mixed
     */
    public function isEnableExcludePage()
    {
        return $this->getHtmlSitemapConfig('exclude_page');
    }

    /**
     * Get exclude page listing
     * @return mixed
     */
    public function getExcludePageListing()
    {
        return $this->getHtmlSitemapConfig('exclude_page_listing');
    }

    /**
     * Get product limit
     * @return mixed
     */
    public function getProductLimit()
    {
        return $this->getHtmlSitemapConfig('product_limit');
    }

    /*********************** XML Sitemap Configuration *************************
     *
     * @param $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getXmlSitemapConfig($code, $storeId = null)
    {
        return $this->getConfigValue(
            self::CONFIG_MODULE_PATH . '/' . self::XML_SITEMAP_CONFIGUARATION . $code,
            $storeId
        );
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnableHomepageOptimization($storeId = null)
    {
        return $this->getXmlSitemapConfig('homepage_optimization', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnableAdditionalLinks($storeId = null)
    {
        return $this->getXmlSitemapConfig('enable_add_link', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return array
     */
    public function getXmlAdditionalLinks($storeId = null)
    {
        return explode("\n", (string) $this->getXmlSitemapConfig('additional_links', $storeId));
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getFrequency($storeId = null)
    {
        return $this->getXmlSitemapConfig('frequency', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getPriority($storeId = null)
    {
        return $this->getXmlSitemapConfig('priority', $storeId);
    }

    /**
     * @param $sendTo
     * @param $fileName
     * @param $emailTemplate
     * @param $storeId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function sendMail($sendTo, $fileName, $emailTemplate, $storeId)
    {
        $siteMapUrl = $this->backendUrl->getRouteUrl('admin/sitemap/index');
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'viewSitemapUrl' => $siteMapUrl,
                    'file_name'      => $fileName
                ])
                ->setFrom($this->getXmlSitemapConfig('sender'))
                ->addTo($sendTo);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            return true;
        } catch (MailException $e) {
            $this->_logger->critical($e->getLogMessage());
        }

        return false;
    }
}
