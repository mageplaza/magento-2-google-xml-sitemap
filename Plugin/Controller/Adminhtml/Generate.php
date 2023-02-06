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

namespace Mageplaza\Sitemap\Plugin\Controller\Adminhtml;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Sitemap\Controller\Adminhtml\Sitemap\Generate as SitemapGenerate;
use Magento\Sitemap\Model\Sitemap;
use Mageplaza\Sitemap\Helper\Data;

/**
 * Class Generate
 * @package Mageplaza\Sitemap\Plugin\Controller\Adminhtml
 */
class Generate
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Sitemap
     */
    protected $sitemap;

    /**
     * @var Data
     */
    protected $helperData;


    /**
     * Generate constructor.
     *
     * @param ManagerInterface $messageManager
     * @param Sitemap $sitemap
     * @param Data $helperData
     */
    public function __construct(
        ManagerInterface $messageManager,
        Sitemap $sitemap,
        Data $helperData
    ) {
        $this->messageManager = $messageManager;
        $this->sitemap        = $sitemap;
        $this->helperData     = $helperData;
    }

    /**
     * @param SitemapGenerate $subject
     * @param array $result
     *
     * @return mixed
     */
    public function afterExecute(SitemapGenerate $subject, $result)
    {
        $siteMapId = $subject->getRequest()->getParam('sitemap_id');
        /* @var Sitemap $sitemap */
        $sitemap   = $this->sitemap->load($siteMapId);
        $sendTo    = [];
        if ($this->helperData->getXmlSitemapConfig('send_to', $sitemap->getStoreId())) {
            $sendTo = explode(',', $this->helperData->getXmlSitemapConfig('send_to', $sitemap->getStoreId()));
        }

        if ($this->helperData->isEnabled()
            && $this->helperData->getXmlSitemapConfig('error_enabled', $sitemap->getStoreId())
            && $sitemap->getId()
            && $this->messageManager->getMessages()->getCountByType(MessageInterface::TYPE_ERROR)
            && $sendTo != null
        ) {
            $this->helperData->sendMail(
                $sendTo,
                $sitemap->getSitemapFilename(),
                $this->helperData->getXmlSitemapConfig('email_template', $sitemap->getStoreId()),
                $this->helperData->getStoreId()
            );
        }

        return $result;
    }
}
